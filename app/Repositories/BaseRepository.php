<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Context\TenantContext;
use App\Exceptions\SecurityCriticalException;
use App\Security\FailClosedGuard;
use Config\Database;
use PDO;
use PDOStatement;

abstract class BaseRepository
{
    protected TenantContext $tenantContext;
    protected PDO $connection;
    private FailClosedGuard $failClosedGuard;
    private bool $tenantFilterDisabled = false;

    public function __construct(
        TenantContext|PDO|null $tenantContextOrConnection = null,
        ?PDO $connection = null
    ) {
        if ($tenantContextOrConnection instanceof TenantContext) {
            $this->tenantContext = $tenantContextOrConnection;
            $this->connection = $connection ?? (new Database())->connect();
            $this->failClosedGuard = new FailClosedGuard();
            return;
        }

        $this->tenantContext = TenantContext::instance();
        $this->connection = $tenantContextOrConnection ?? $connection ?? (new Database())->connect();
        $this->failClosedGuard = new FailClosedGuard();
    }

    protected function applyTenantFilter(
        string &$query,
        array &$parameters,
        string $tenantColumn = 'company_id'
    ): void
    {
        if ($this->tenantFilterDisabled) {
            return;
        }

        $companyId = $this->tenantContext->getCompanyId();
        if ($companyId === null) {
            throw new SecurityCriticalException(
                SecurityCriticalException::CODE_TENANT_CONTEXT_MISSING,
                'O contexto do tenant não foi inicializado.'
            );
        }

        $predicate = $tenantColumn . ' = :company_id';
        $trimmedQuery = rtrim($query);
        $suffix = '';
        $insertAt = null;

        if (preg_match(
            '/\s+(GROUP\s+BY|ORDER\s+BY|LIMIT|FOR\s+UPDATE)\b/i',
            $trimmedQuery,
            $matches,
            PREG_OFFSET_CAPTURE
        ) === 1) {
            $insertAt = $matches[0][1];
        }

        if ($insertAt !== null) {
            $suffix = substr($trimmedQuery, $insertAt);
            $trimmedQuery = rtrim(substr($trimmedQuery, 0, $insertAt));
        }

        if (preg_match('/\bWHERE\b/i', $trimmedQuery) === 1) {
            $query = $trimmedQuery . ' AND ' . $predicate;
        } else {
            $query = $trimmedQuery . ' WHERE ' . $predicate;
        }

        $query .= $suffix;
        $parameters['company_id'] = $companyId;

        $this->assertTenantScopedQuery($query, $parameters);
    }

    protected function assertTenantContext(): int
    {
        $companyId = $this->tenantContext->getCompanyId();
        if ($companyId === null) {
            throw new SecurityCriticalException(
                SecurityCriticalException::CODE_TENANT_CONTEXT_MISSING,
                'O contexto do tenant não foi inicializado.'
            );
        }

        return $companyId;
    }

    protected function prepareTenantStatement(
        string $query,
        array $parameters,
        string $scope = FailClosedGuard::SCOPE_OPERATIONAL
    ): PDOStatement {
        $this->failClosedGuard->assertKnownScope($scope);

        if ($scope === FailClosedGuard::SCOPE_OPERATIONAL) {
            $this->assertTenantScopedQuery($query, $parameters);
        }

        return $this->connection->prepare($query);
    }

    protected function prepareSystemStatement(string $query): PDOStatement
    {
        return $this->prepareTenantStatement(
            $query,
            [],
            FailClosedGuard::SCOPE_SYSTEM
        );
    }

    protected function prepareGlobalStatement(string $query): PDOStatement
    {
        return $this->prepareTenantStatement(
            $query,
            [],
            FailClosedGuard::SCOPE_GLOBAL
        );
    }

    protected function queryGlobal(string $query): \PDOStatement|false
    {
        $this->failClosedGuard->assertKnownScope(FailClosedGuard::SCOPE_GLOBAL);

        return $this->connection->query($query);
    }

    protected function withoutTenantFilter(callable $callback): mixed
    {
        $previousState = $this->tenantFilterDisabled;
        $this->tenantFilterDisabled = true;

        try {
            return $callback();
        } finally {
            $this->tenantFilterDisabled = $previousState;
        }
    }

    private function assertTenantScopedQuery(string $query, array $parameters): void
    {
        $this->failClosedGuard->assertOperationalQueryIsTenantScoped(
            $query,
            $parameters,
            $this->tenantContext->getCompanyId()
        );
    }
}
