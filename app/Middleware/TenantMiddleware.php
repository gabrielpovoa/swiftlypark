<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Context\IdentityContext;
use App\Context\TenantContext;
use App\Exceptions\ForbiddenException;
use App\Exceptions\SecurityCriticalException;
use App\Exceptions\UnauthorizedException;
use App\Companies\Domain\Company;
use App\Repositories\AuditLogRepository;
use App\Repositories\TenantRepository;
use App\Services\SecurityAuditService;
use Config\Database;
use PDO;

final class TenantMiddleware
{
    public function __construct(
        private ?TenantContext $tenantContext = null,
        private ?PDO $connection = null
    ) {
        $this->tenantContext ??= TenantContext::instance();
    }

    public function handle(callable $next): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $identity = IdentityContext::current();
        $connection = $this->connection ?? (new Database())->connect();
        $repository = new TenantRepository($connection);

        $companyId = $this->resolveCompanyId($repository, $identity->userId());

        if ($companyId === null) {
            throw new ForbiddenException('tenant.access', 'Não existe uma empresa válida para esta requisição.');
        }

        $company = $repository->findCompanyById($companyId);

        if ($company === null) {
            throw new ForbiddenException('tenant.access', 'A empresa solicitada não foi localizada.');
        }

        if (!$repository->hasMembership($identity->userId(), $companyId)
            && !$this->isValidSupportImpersonation($connection, $identity->userId(), $companyId)
        ) {
            (new SecurityAuditService(
                new AuditLogRepository($connection),
                $identity,
                $this->tenantContext
            ))->recordCrossTenantAccess(
                $this->route(),
                $companyId,
                'Usuário autenticado tentou operar em empresa sem vínculo.'
            );

            throw new SecurityCriticalException(
                SecurityCriticalException::CODE_CROSS_TENANT_ACCESS,
                'Você não possui acesso a esta empresa.'
            );
        }

        $this->tenantContext->setCompany(
            Company::reconstitute(
                (int) $company['id'],
                (string) $company['name'],
                (string) $company['slug'],
                $company['logo_path'] !== null ? (string) $company['logo_path'] : null,
                true
            )
        );

        $_SESSION['company_id'] = $companyId;

        try {
            $next();
        } catch (SecurityCriticalException $exception) {
            (new SecurityAuditService(
                new AuditLogRepository($connection),
                $identity,
                $this->tenantContext
            ))->recordCriticalQueryBlocked(
                $this->route(),
                $exception->securityCode(),
                $exception->getMessage()
            );

            throw $exception;
        } finally {
            $this->tenantContext->clear();
        }
    }

    private function resolveCompanyId(TenantRepository $repository, int $userId): ?int
    {
        $impersonatedCompanyId = filter_var(
            $_SESSION['support_impersonation']['company_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $headerCompanyId = $this->resolveCompanyIdFromHeaders();

        $sessionCompanyId = filter_var(
            $_SESSION['company_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($impersonatedCompanyId !== false
            && $impersonatedCompanyId !== null
            && ($headerCompanyId === null || $headerCompanyId === (int) $impersonatedCompanyId)
            && ($sessionCompanyId === false || $sessionCompanyId === null || (int) $sessionCompanyId === (int) $impersonatedCompanyId)
        ) {
            return (int) $impersonatedCompanyId;
        }

        if ($headerCompanyId !== null) {
            return $headerCompanyId;
        }

        if ($sessionCompanyId !== false && $sessionCompanyId !== null) {
            return (int) $sessionCompanyId;
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host !== '') {
            $segments = explode('.', $host);
            $subdomain = $segments[0] ?? '';

            if ($subdomain !== '' && $subdomain !== 'www' && $subdomain !== 'localhost') {
                $company = $repository->findCompanyBySlug($subdomain);
                if ($company !== null) {
                    return (int) $company['id'];
                }
            }
        }

        $fallbackCompany = $repository->findFirstCompanyForUser($userId);

        return $fallbackCompany !== null ? (int) $fallbackCompany['id'] : null;
    }

    private function resolveCompanyIdFromHeaders(): ?int
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

    private function route(): string
    {
        return trim((string) ($_GET['url'] ?? $_SERVER['REQUEST_URI'] ?? ''), '/');
    }

    private function isValidSupportImpersonation(PDO $connection, int $userId, int $companyId): bool
    {
        $isPlatformAdmin = $this->hasGlobalPlatformRole($connection, $userId);
        $impersonatedCompanyId = filter_var(
            $_SESSION['support_impersonation']['company_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        return $isPlatformAdmin
            && $impersonatedCompanyId !== false
            && (int) $impersonatedCompanyId === $companyId;
    }

    private function hasGlobalPlatformRole(PDO $connection, int $userId): bool
    {
        try {
            $authorization = (new \App\Authorization\Services\RolePermissionResolver(
                new \App\Authorization\Repositories\RbacRepository($connection)
            ))->resolve($userId, null);
        } catch (\Throwable) {
            return false;
        }

        return in_array('super-admin', $authorization->roleSlugs(), true)
            || in_array('master', $authorization->roleSlugs(), true);
    }
}
