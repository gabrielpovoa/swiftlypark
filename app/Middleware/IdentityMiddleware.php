<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Authorization\Repositories\RbacRepository;
use App\Authorization\Services\RolePermissionResolver;
use App\Context\IdentityContext;
use App\Context\RequestIdentity;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\AccessRevokedException;
use App\Identity\Infrastructure\UserAccessRepository;
use App\Repositories\TenantRepository;
use Config\Database;
use DateTimeImmutable;
use DateTimeZone;

final class IdentityMiddleware
{
    public function handle(callable $next): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = filter_var(
            $_SESSION['user_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($userId === false) {
            throw new UnauthorizedException('A autenticação é obrigatória.');
        }

        $connection = (new Database())->connect();

        if (!(new UserAccessRepository($connection))->isActive($userId)) {
            $_SESSION = [];
            session_destroy();

            throw new AccessRevokedException(
                'Seu acesso foi revogado. Entre em contato com o administrador do sistema.'
            );
        }

        $tenantRepository = new TenantRepository($connection);
        $resolver = new RolePermissionResolver(new RbacRepository($connection));
        $companyId = $this->isGlobalDashboardRequest()
            ? null
            : $this->resolveCompanyId($tenantRepository, $resolver, $userId);

        if ($companyId !== null) {
            $_SESSION['company_id'] = $companyId;
        }

        $globalAuthorization = $resolver->resolve($userId, null);
        $authorization = $this->supportAuthorization($resolver, $companyId)
            ?? ($this->hasPlatformAdminRole($globalAuthorization->roleSlugs())
                ? $globalAuthorization
                : $resolver->resolve($userId, $companyId));
        $_SESSION['permissions'] = $authorization->permissions();
        $_SESSION['role_slugs'] = $authorization->roleSlugs();
        $_SESSION['role_metadata'] = $authorization
            ->roleMetadata()
            ->toArray();

        $email = filter_var(
            $_SESSION['user_email'] ?? null,
            FILTER_VALIDATE_EMAIL
        );

        if ($email === false) {
            throw new UnauthorizedException('A identidade da sessão é inválida.');
        }

        $identity = new RequestIdentity(
            $userId,
            strtolower($email),
            $this->resolveIpAddress(),
            $this->uuid(),
            new DateTimeImmutable('now', new DateTimeZone('UTC')),
            $authorization->permissions(),
            $authorization->roleSlugs(),
            $authorization->roleMetadata()->toArray()
        );

        IdentityContext::set($identity);

        try {
            $next();
        } finally {
            IdentityContext::clear();
        }
    }

    private function resolveCompanyId(
        TenantRepository $tenantRepository,
        RolePermissionResolver $resolver,
        int $userId
    ): ?int {
        foreach ($this->companyCandidates() as $requestedCompanyId) {
            if ($tenantRepository->hasMembership($userId, $requestedCompanyId)) {
                return $requestedCompanyId;
            }

            $globalAuthorization = $resolver->resolve($userId);
            $impersonatedCompanyId = filter_var(
                $_SESSION['support_impersonation']['company_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($this->hasPlatformAdminRole($globalAuthorization->roleSlugs())
                && $impersonatedCompanyId !== false
                && (int) $impersonatedCompanyId === $requestedCompanyId
            ) {
                return $requestedCompanyId;
            }
        }

        return null;
    }

    private function supportAuthorization(
        RolePermissionResolver $resolver,
        ?int $companyId
    ): ?\App\Authorization\DTO\ResolvedAuthorizationContext {
        if ($companyId === null) {
            return null;
        }

        if (($_SESSION['support_impersonation']['profile_selected'] ?? false) !== true) {
            return null;
        }

        $supportCompanyId = $this->normalizeCompanyId(
            $_SESSION['support_impersonation']['company_id'] ?? null
        );

        if ($supportCompanyId === null || $supportCompanyId !== $companyId) {
            return null;
        }

        $roleSlug = trim((string) ($_SESSION['support_impersonation']['simulated_role'] ?? ''));
        if ($roleSlug === '' || $roleSlug === 'super-admin') {
            return null;
        }

        return $resolver->resolveSimulatedRole(
            $roleSlug,
            is_array($_SESSION['support_impersonation']['extra_permissions'] ?? null)
                ? $_SESSION['support_impersonation']['extra_permissions']
                : []
        );
    }

    private function isGlobalDashboardRequest(): bool
    {
        $route = trim((string) ($_GET['url'] ?? parse_url(
            (string) ($_SERVER['REQUEST_URI'] ?? ''),
            PHP_URL_PATH
        ) ?? ''), '/');

        return $route === 'admin/dashboard';
    }

    /**
     * Keep authorization aligned with the tenant that the request will use.
     */
    private function companyCandidates(): array
    {
        $supportCompanyId = $this->normalizeCompanyId(
            $_SESSION['support_impersonation']['company_id'] ?? null
        );
        $headerCompanyId = $this->companyIdFromHeaders();
        $sessionCompanyId = $this->normalizeCompanyId($_SESSION['company_id'] ?? null);
        $candidates = [];

        if ($supportCompanyId !== null
            && ($headerCompanyId === null || $headerCompanyId === $supportCompanyId)
            && ($sessionCompanyId === null || $sessionCompanyId === $supportCompanyId)
        ) {
            $candidates[] = $supportCompanyId;
        }

        $candidates[] = $headerCompanyId;
        $candidates[] = $sessionCompanyId;

        $ids = [];
        foreach ($candidates as $candidate) {
            if ($candidate !== null && !in_array($candidate, $ids, true)) {
                $ids[] = $candidate;
            }
        }

        return $ids;
    }

    private function normalizeCompanyId(mixed $value): ?int
    {
        $companyId = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        return $companyId !== false ? (int) $companyId : null;
    }

    private function companyIdFromHeaders(): ?int
    {
        foreach (['HTTP_X_COMPANY_ID', 'HTTP_X_TENANT_ID'] as $headerName) {
            $value = $_SERVER[$headerName] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $companyId = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($companyId !== false) {
                return (int) $companyId;
            }
        }

        return null;
    }

    private function resolveIpAddress(): string
    {
        $candidate = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        return filter_var($candidate, FILTER_VALIDATE_IP) !== false
            ? $candidate
            : '0.0.0.0';
    }

    private function hasPlatformAdminRole(array $roles): bool
    {
        return in_array('super-admin', $roles, true)
            || in_array('master', $roles, true);
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20)
        );
    }

}
