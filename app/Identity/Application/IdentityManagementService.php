<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Context\RequestIdentity;
use App\Contracts\PasswordRecoveryMailerInterface;
use App\Context\TenantContext;
use App\Exceptions\ForbiddenException;
use App\Identity\Domain\Events\UserReactivatedEvent;
use App\Identity\Infrastructure\IdentityManagementRepository;
use App\Repositories\AuditLogRepository;
use App\Services\AuthorizationService;
use App\Services\PasswordGeneratorService;
use App\Transactions\TransactionManager;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final class IdentityManagementService
{
    public function __construct(
        private IdentityManagementRepository $users,
        private AuditLogRepository $auditLogs,
        private TransactionManager $transactions,
        private RequestIdentity $identity,
        private ?TenantContext $tenantContext = null,
        private ?PasswordGeneratorService $passwords = null,
        private ?PasswordRecoveryMailerInterface $mailer = null
    ) {
        $this->tenantContext ??= TenantContext::instance();
        $this->passwords ??= new PasswordGeneratorService();
        if ($this->mailer === null) {
            throw new RuntimeException('Mailer assíncrono não configurado para Identity.');
        }
    }

    public function revoke(int $targetUserId): void
    {
        (new AuthorizationService($this->identity))->check('identity.manage');
        $this->assertTargetInScope($targetUserId);

        if ($targetUserId === $this->identity->userId()) {
            throw new ForbiddenException(
                'identity.manage',
                'Você não pode revogar o próprio acesso.'
            );
        }

        if (!in_array('super-admin', $this->identity->roleSlugs(), true)
            && $this->users->activeCompanyMembershipsCount($targetUserId) > 1) {
            throw new ForbiddenException(
                'identity.manage',
                'Este usuário pertence a outras empresas e não pode ser revogado globalmente.'
            );
        }

        $this->transactions->run(function () use ($targetUserId): void {
            $target = $this->users->findUserForUpdate($targetUserId);

            if ($target === null) {
                throw new RuntimeException('Usuário não encontrado.');
            }

            if ($target['deleted_at'] !== null) {
                throw new RuntimeException('O acesso deste usuário já foi revogado.');
            }

            if ($this->users->hasRole($targetUserId, 'super-admin')
                && $this->users->countActiveSuperAdminsForUpdate() <= 1) {
                throw new ForbiddenException(
                    'identity.manage',
                    'O último Super-Admin ativo não pode ser revogado.'
                );
            }

            $this->users->revoke($targetUserId);
            $this->audit(
                'ACCESS_REVOKED',
                $targetUserId,
                ['target_user_id' => $targetUserId, 'target_email' => $target['email']]
            );
        });
    }

    public function syncPermissions(int $targetUserId, array $permissionIds): void
    {
        (new AuthorizationService($this->identity))->check('identity.manage');
        $this->assertTargetInScope($targetUserId);

        if ($targetUserId === $this->identity->userId()
            && !in_array('super-admin', $this->identity->roleSlugs(), true)) {
            throw new ForbiddenException(
                'identity.manage',
                'Apenas SUPER-ADMIN pode alterar as próprias permissões extras.'
            );
        }

        $permissionIds = array_values(array_unique(array_filter(
            array_map('intval', $permissionIds),
            static fn (int $id): bool => $id > 0
        )));

        $this->transactions->run(function () use (
            $targetUserId,
            $permissionIds
        ): void {
            $target = $this->users->findUserForUpdate($targetUserId);

            if ($target === null || $target['deleted_at'] !== null) {
                throw new RuntimeException('Usuário indisponível para alteração.');
            }

            $companyId = $this->tenantContext->getCompanyId();
            $isSuperAdmin = in_array('super-admin', $this->identity->roleSlugs(), true);
            $before = $isSuperAdmin
                ? $this->users->directPermissionIds($targetUserId)
                : $this->users->companyPermissionIds($targetUserId, (int) $companyId);
            if ($isSuperAdmin) {
                $this->users->syncPermissions(
                    $targetUserId,
                    $permissionIds,
                    $this->identity->userId()
                );
            } else {
                $this->users->syncCompanyPermissions(
                    $targetUserId,
                    (int) $companyId,
                    $permissionIds,
                    $this->identity->userId()
                );
            }
            $after = $isSuperAdmin
                ? $this->users->directPermissionIds($targetUserId)
                : $this->users->companyPermissionIds($targetUserId, (int) $companyId);
            $this->audit('USER_PERMISSIONS_UPDATED', $targetUserId, [
                'target_user_id' => $targetUserId,
                'target_email' => $target['email'],
                'permissions_added' => $this->users->permissionSlugs(
                    array_values(array_diff($after, $before))
                ),
                'permissions_removed' => $this->users->permissionSlugs(
                    array_values(array_diff($before, $after))
                ),
            ]);
        });
    }

    public function reactivate(int $targetUserId): void
    {
        (new AuthorizationService($this->identity))->check('identity.manage');
        $this->assertTargetInScope($targetUserId);

        if ($targetUserId === $this->identity->userId()) {
            throw new ForbiddenException(
                'identity.manage',
                'Você não pode reativar o próprio acesso.'
            );
        }

        $temporaryPassword = $this->passwords->temporary();
        $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
        $target = null;

        $this->transactions->run(function () use (
            $targetUserId,
            $passwordHash,
            &$target
        ): void {
            $target = $this->users->findUserForUpdate($targetUserId);

            if ($target === null) {
                throw new RuntimeException('Usuário não encontrado.');
            }

            if ($target['deleted_at'] === null) {
                throw new RuntimeException('A ação de reativação está disponível apenas para usuários inativos.');
            }

            $this->users->reactivate($targetUserId, $passwordHash);
            $this->users->updateLoginPassword((int) $target['id_login'], $passwordHash);
            $this->audit('UPDATE', $targetUserId, [
                'message' => sprintf(
                    '[Audit] User %d reactivated by administrator %d',
                    $targetUserId,
                    $this->identity->userId()
                ),
                'target_user_id' => $targetUserId,
                'target_email' => $target['email'],
                'reactivated_by' => $this->identity->userId(),
                'password_reset_required' => true,
            ]);
        });

        $this->dispatch(new UserReactivatedEvent(
            $targetUserId,
            (string) $target['email'],
            $this->identity->userId()
        ));
        $this->mailer->sendReactivationPassword(
            (string) $target['email'],
            $temporaryPassword
        );
    }

    private function audit(string $action, int $targetId, array $payload): void
    {
        $this->auditLogs->insert([
            'user_id' => $this->identity->userId(),
            'company_id' => $this->tenantContext->getCompanyId(),
            'actor_email' => $this->identity->email(),
            'action' => $action,
            'entity' => 'identity',
            'entity_id' => (string) $targetId,
            'old_values' => null,
            'new_values' => json_encode($payload, JSON_THROW_ON_ERROR),
            'ip_address' => $this->identity->ipAddress(),
            'request_id' => $this->identity->requestId(),
            'created_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                ->format('Y-m-d H:i:s.u'),
        ]);
    }

    private function assertTargetInScope(int $targetUserId): void
    {
        if (in_array('super-admin', $this->identity->roleSlugs(), true)) {
            return;
        }

        if ($this->users->hasRole($targetUserId, 'super-admin')) {
            throw new ForbiddenException(
                'identity.manage',
                'O perfil do Super-Admin é restrito à administração global.'
            );
        }

        $companyId = $this->tenantContext->getCompanyId();
        if ($companyId === null || !$this->users->hasCompanyMembership($targetUserId, $companyId)) {
            throw new ForbiddenException(
                'identity.manage',
                'Você não pode gerenciar usuários de outra empresa.'
            );
        }
    }

    private function dispatch(object $event): void
    {
        // Hook central para plugar um EventBus sem espalhar acoplamento pela camada de domínio.
    }
}
