<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Authorization\Repositories\RbacRepository;
use App\Authorization\Services\NavigationService;
use App\Authorization\Services\RolePermissionResolver;
use App\Context\IdentityContext;
use App\Context\TenantContext;
use App\Companies\Domain\Company;
use App\Repositories\AuditLogRepository;
use App\Repositories\TenantRepository;
use App\Services\AuthorizationService;
use App\Services\SecurityAuditService;
use Config\Database;
use Core\Controller;
use Throwable;

final class ApiTenantController extends Controller
{
    public function tenants(): void
    {
        $this->json(function (): array {
            $this->startSession();
            $identity = IdentityContext::current();
            $connection = (new Database())->connect();
            $repository = new TenantRepository($connection);
            $isPlatformAdmin = $this->hasGlobalPlatformRole($connection, $identity->userId());
            $tenants = $this->normalizeTenants(
                $isPlatformAdmin
                    ? $repository->findSwitchableCompaniesForPlatformUser($identity->userId())
                    : $repository->findCompaniesForUser($identity->userId())
            );
            $currentCompanyId = $this->currentCompanyId($repository, $identity->userId(), $tenants, $isPlatformAdmin);

            return [
                'current_company_id' => $currentCompanyId,
                'can_switch' => count($tenants) > 1
                    || $isPlatformAdmin,
                'tenants' => $tenants,
            ];
        });
    }

    public function switchTenant(): void
    {
        $this->json(function (): array {
            $this->startSession();
            $identity = IdentityContext::current();
            $payload = $this->payload();
            $companyId = filter_var(
                $payload['company_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($companyId === false) {
                http_response_code(422);

                return ['error' => 'company_id inválido.'];
            }

            $connection = (new Database())->connect();
            $repository = new TenantRepository($connection);
            $hasMembership = $repository->hasMembership($identity->userId(), (int) $companyId);
            $isPlatformAdmin = $this->hasGlobalPlatformRole($connection, $identity->userId());

            if (!$isPlatformAdmin && !$hasMembership) {
                (new SecurityAuditService(
                    new AuditLogRepository($connection),
                    $identity,
                    TenantContext::instance()
                ))->recordCrossTenantAccess(
                    'api/v1/tenant/switch',
                    (int) $companyId,
                    'Usuário tentou trocar para empresa sem vínculo.'
                );

                http_response_code(403);

                return ['error' => 'Você não possui acesso a esta empresa.'];
            }

            $company = $repository->findCompanyById((int) $companyId);
            if ($company === null) {
                http_response_code(404);

                return ['error' => 'Empresa não encontrada.'];
            }

            $_SESSION['company_id'] = (int) $company['id'];
            if ($isPlatformAdmin) {
                $_SESSION['support_impersonation'] = [
                    'super_admin_user_id' => $identity->userId(),
                    'company_id' => (int) $company['id'],
                    'company_name' => (string) $company['name'],
                    'profile_selected' => false,
                    'simulated_role' => null,
                    'simulated_role_label' => 'Super-Admin (sem simulação)',
                    'extra_permissions' => [],
                    'started_at' => $identity->requestedAt()->format('Y-m-d H:i:s.u'),
                ];
                $this->recordImpersonationStart(new AuditLogRepository($connection), $company, null);
            } else {
                unset($_SESSION['support_impersonation']);
            }

            TenantContext::instance()->setCompany(Company::reconstitute(
                (int) $company['id'],
                (string) $company['name'],
                (string) $company['slug'],
                $company['logo_path'] !== null ? (string) $company['logo_path'] : null,
                true
            ));

            $resolver = new RolePermissionResolver(
                new RbacRepository($connection)
            );
            $authorization = $isPlatformAdmin
                ? $resolver->resolve($identity->userId(), null)
                : $resolver->resolve($identity->userId(), (int) $company['id']);
            $_SESSION['permissions'] = $authorization->permissions();
            $_SESSION['role_slugs'] = $authorization->roleSlugs();
            $_SESSION['role_metadata'] = $authorization->roleMetadata()->toArray();

            return [
                'current_company' => $this->normalizeCompany($company),
                'authorization' => $this->authorizationPayload(
                    $authorization->permissions(),
                    $authorization->roleSlugs(),
                    $authorization->roleMetadata()->toArray()
                ),
                'support_impersonation' => $isPlatformAdmin,
                'support_profile' => $_SESSION['support_impersonation'] ?? null,
                'redirect_url' => '/operational/dashboard',
            ];
        });
    }

    public function switchGlobal(): void
    {
        $this->json(function (): array {
            $this->startSession();
            $connection = (new Database())->connect();
            $identity = IdentityContext::current();
            if (!$this->hasGlobalPlatformRole($connection, $identity->userId())) {
                http_response_code(403);

                return ['error' => 'Apenas administradores globais podem usar este contexto.'];
            }

            $authorization = (new RolePermissionResolver(
                new RbacRepository($connection)
            ))->resolve($identity->userId(), null);
            unset($_SESSION['company_id'], $_SESSION['support_impersonation']);
            $_SESSION['permissions'] = $authorization->permissions();
            $_SESSION['role_slugs'] = $authorization->roleSlugs();
            $_SESSION['role_metadata'] = $authorization->roleMetadata()->toArray();
            TenantContext::instance()->clear();

            return [
                'current_company' => null,
                'redirect_url' => '/admin/dashboard',
            ];
        });
    }

    public function supportProfile(): void
    {
        $this->json(function (): array {
            $this->startSession();
            $identity = IdentityContext::current();
            $payload = $this->payload();
            $connection = (new Database())->connect();
            $repository = new TenantRepository($connection);

            if (!$this->hasGlobalPlatformRole($connection, $identity->userId())) {
                http_response_code(403);

                return ['error' => 'Apenas perfis globais podem alterar o modo suporte.'];
            }

            $companyId = filter_var(
                $_SESSION['support_impersonation']['company_id'] ?? $_SESSION['company_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($companyId === false) {
                http_response_code(422);

                return ['error' => 'Selecione uma empresa antes de trocar o perfil de suporte.'];
            }

            $company = $repository->findCompanyById((int) $companyId);
            if ($company === null) {
                http_response_code(404);

                return ['error' => 'Empresa não encontrada.'];
            }

            $roleSlug = trim((string) ($payload['simulated_role'] ?? ''));
            $useGlobalProfile = $roleSlug === '__super_admin__';
            $supportRoles = $repository->findSupportRoles();
            $role = $useGlobalProfile
                ? ['slug' => null, 'label' => 'Super-Admin (sem simulação)']
                : $this->findSupportRole($supportRoles, $roleSlug);
            if ($role === null) {
                http_response_code(422);

                return ['error' => 'Perfil de suporte inválido.'];
            }

            $extraPermissions = $payload['extra_permissions'] ?? [];
            if (!is_array($extraPermissions)) {
                $extraPermissions = [];
            }
            $extraPermissions = array_values(array_unique(array_filter(
                array_map('intval', $extraPermissions),
                static fn (int $id): bool => $id > 0
            )));

            $_SESSION['company_id'] = (int) $company['id'];
            $_SESSION['support_impersonation'] = [
                'super_admin_user_id' => $identity->userId(),
                'company_id' => (int) $company['id'],
                'company_name' => (string) $company['name'],
                'profile_selected' => !$useGlobalProfile,
                'simulated_role' => $role['slug'],
                'simulated_role_label' => (string) $role['label'],
                'extra_permissions' => $useGlobalProfile ? [] : $extraPermissions,
                'started_at' => $_SESSION['support_impersonation']['started_at']
                    ?? $identity->requestedAt()->format('Y-m-d H:i:s.u'),
            ];

            $resolver = new RolePermissionResolver(new RbacRepository($connection));
            $authorization = $useGlobalProfile
                ? $resolver->resolve($identity->userId(), null)
                : $resolver->resolveSimulatedRole((string) $role['slug'], $extraPermissions);
            $_SESSION['permissions'] = $authorization->permissions();
            $_SESSION['role_slugs'] = $authorization->roleSlugs();
            $_SESSION['role_metadata'] = $authorization->roleMetadata()->toArray();

            $this->recordSupportProfileChanged(
                new AuditLogRepository($connection),
                $company,
                $role['slug'] !== null ? (string) $role['slug'] : null,
                (string) $role['label'],
                $useGlobalProfile ? [] : $extraPermissions
            );

            return [
                'current_company' => $this->normalizeCompany($company),
                'authorization' => $this->authorizationPayload(
                    $authorization->permissions(),
                    $authorization->roleSlugs(),
                    $authorization->roleMetadata()->toArray()
                ),
                'support_profile' => $_SESSION['support_impersonation'],
                'redirect_url' => '/operational/dashboard',
            ];
        });
    }

    public function permissionsContext(): void
    {
        $this->json(function (): array {
            $this->startSession();
            $identity = IdentityContext::current();
            $navigation = new NavigationService(new AuthorizationService($identity));
            $repository = new TenantRepository((new Database())->connect());
            $company = null;
            $companyId = filter_var(
                $_SESSION['company_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($companyId !== false) {
                $company = $repository->findCompanyById((int) $companyId);
            }

            return [
                'current_company' => $company !== null ? $this->normalizeCompany($company) : null,
                'authorization' => $this->authorizationPayload(
                    $identity->permissions(),
                    $identity->roleSlugs(),
                    $identity->roleMetadata()
                ),
                'navigation' => [
                    'items' => $navigation->allowedItems($navigation->defaultItems()),
                ],
            ];
        });
    }

    private function json(callable $callback): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            echo json_encode($callback(), JSON_THROW_ON_ERROR);
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Não foi possível processar a requisição.']);
        } finally {
            TenantContext::clearStatic();
        }
    }

    private function payload(): array
    {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);

            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    private function currentCompanyId(TenantRepository $repository, int $userId, array $tenants, bool $isPlatformAdmin = false): ?int
    {
        $sessionCompanyId = filter_var(
            $_SESSION['company_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($sessionCompanyId !== false
            && ($isPlatformAdmin || $repository->hasMembership($userId, (int) $sessionCompanyId))
        ) {
            return (int) $sessionCompanyId;
        }

        return isset($tenants[0]['id']) ? (int) $tenants[0]['id'] : null;
    }

    private function normalizeTenants(array $tenants): array
    {
        return array_map(
            fn (array $tenant): array => [
                'id' => (int) $tenant['id'],
                'name' => (string) $tenant['name'],
                'slug' => (string) $tenant['slug'],
                'logo_path' => $tenant['logo_path'] !== null ? (string) $tenant['logo_path'] : null,
                'has_membership' => (bool) ($tenant['has_membership'] ?? true),
                'role' => $tenant['role_slug'] !== null ? [
                    'slug' => (string) $tenant['role_slug'],
                    'label' => (string) $tenant['role_label'],
                ] : null,
            ],
            $tenants
        );
    }

    private function normalizeCompany(array $company): array
    {
        return [
            'id' => (int) $company['id'],
            'name' => (string) $company['name'],
            'slug' => (string) $company['slug'],
            'logo_path' => $company['logo_path'] !== null ? (string) $company['logo_path'] : null,
        ];
    }

    private function authorizationPayload(array $permissions, array $roles, array $roleMetadata): array
    {
        return [
            'role' => $roleMetadata,
            'roles' => array_values($roles),
            'permissions' => array_values($permissions),
        ];
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function isPlatformAdmin(array $roles): bool
    {
        return in_array('super-admin', $roles, true);
    }

    private function hasGlobalPlatformRole(\PDO $connection, int $userId): bool
    {
        $authorization = (new RolePermissionResolver(
            new RbacRepository($connection)
        ))->resolve($userId, null);

        return $this->isPlatformAdmin($authorization->roleSlugs());
    }

    private function findSupportRole(array $roles, string $roleSlug): ?array
    {
        foreach ($roles as $role) {
            if ((string) $role['slug'] === $roleSlug && $roleSlug !== 'super-admin') {
                return $role;
            }
        }

        return null;
    }

    private function recordImpersonationStart(AuditLogRepository $auditLogs, array $company, ?array $role): void
    {
        $identity = IdentityContext::current();

        $auditLogs->insert([
            'user_id' => $identity->userId(),
            'company_id' => (int) $company['id'],
            'actor_email' => $identity->email(),
            'action' => 'UPDATE',
            'entity' => 'support_impersonation',
            'entity_id' => (string) $company['id'],
            'old_values' => null,
            'new_values' => json_encode([
                'event' => 'IMPERSONATION_STARTED',
                'super_admin_user_id' => $identity->userId(),
                'target_company_id' => (int) $company['id'],
                'target_company_name' => (string) $company['name'],
                'simulated_role' => $role['slug'] ?? null,
                'simulated_role_label' => $role['label'] ?? 'Super-Admin (sem simulação)',
                'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ], JSON_THROW_ON_ERROR),
            'ip_address' => $identity->ipAddress(),
            'request_id' => $identity->requestId(),
            'created_at' => $identity->requestedAt()->format('Y-m-d H:i:s.u'),
        ]);
    }

    private function recordSupportProfileChanged(
        AuditLogRepository $auditLogs,
        array $company,
        ?string $roleSlug,
        string $roleLabel,
        array $extraPermissions
    ): void {
        $identity = IdentityContext::current();

        $auditLogs->insert([
            'user_id' => $identity->userId(),
            'company_id' => (int) $company['id'],
            'actor_email' => $identity->email(),
            'action' => 'UPDATE',
            'entity' => 'support_impersonation',
            'entity_id' => (string) $company['id'],
            'old_values' => null,
            'new_values' => json_encode([
                'event' => 'SUPPORT_PROFILE_CHANGED',
                'super_admin_user_id' => $identity->userId(),
                'target_company_id' => (int) $company['id'],
                'target_company_name' => (string) $company['name'],
                'simulated_role' => $roleSlug,
                'simulated_role_label' => $roleLabel,
                'extra_permissions' => $extraPermissions,
            ], JSON_THROW_ON_ERROR),
            'ip_address' => $identity->ipAddress(),
            'request_id' => $identity->requestId(),
            'created_at' => $identity->requestedAt()->format('Y-m-d H:i:s.u'),
        ]);
    }
}
