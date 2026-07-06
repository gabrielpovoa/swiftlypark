<?php

declare(strict_types=1);

namespace App\Identity\Services;

use App\Context\RequestIdentity;
use App\Exceptions\ForbiddenException;
use App\Identity\Repositories\IdentityManagementRepository;
use App\Repositories\AuditLogRepository;
use App\Services\AuthorizationService;
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
        private RequestIdentity $identity
    ) {
    }

    public function revoke(int $targetUserId): void
    {
        (new AuthorizationService($this->identity))->check('identity.manage');

        if ($targetUserId === $this->identity->userId()) {
            throw new ForbiddenException(
                'identity.manage',
                'Você não pode revogar o próprio acesso.'
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

            if ($this->users->hasRole($targetUserId, 'master')
                && $this->users->countActiveMastersForUpdate() <= 1) {
                throw new ForbiddenException(
                    'identity.manage',
                    'O último usuário Master ativo não pode ser revogado.'
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

        if ($targetUserId === $this->identity->userId()) {
            throw new ForbiddenException(
                'identity.manage',
                'Você não pode alterar as próprias permissões extras.'
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

            $before = $this->users->directPermissionIds($targetUserId);
            $this->users->syncPermissions(
                $targetUserId,
                $permissionIds,
                $this->identity->userId()
            );
            $after = $this->users->directPermissionIds($targetUserId);
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

    private function audit(string $action, int $targetId, array $payload): void
    {
        $this->auditLogs->insert([
            'user_id' => $this->identity->userId(),
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
}
