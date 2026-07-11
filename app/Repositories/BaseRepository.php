<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Context\TenantContext;
use App\Exceptions\TenantNotSetException;
use Config\Database;
use PDO;

abstract class BaseRepository
{
    protected TenantContext $tenantContext;
    protected PDO $connection;

    public function __construct(
        TenantContext|PDO|null $tenantContextOrConnection = null,
        ?PDO $connection = null
    ) {
        if ($tenantContextOrConnection instanceof TenantContext) {
            $this->tenantContext = $tenantContextOrConnection;
            $this->connection = $connection ?? (new Database())->connect();
            return;
        }

        $this->tenantContext = TenantContext::instance();
        $this->connection = $tenantContextOrConnection ?? $connection ?? (new Database())->connect();
    }

    protected function applyTenantFilter(
        string &$query,
        array &$parameters,
        string $tenantColumn = 'company_id'
    ): void
    {
        $companyId = $this->tenantContext->getCompanyId();
        if ($companyId === null) {
            throw new TenantNotSetException();
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
    }

    protected function assertTenantContext(): int
    {
        $companyId = $this->tenantContext->getCompanyId();
        if ($companyId === null) {
            throw new TenantNotSetException();
        }

        return $companyId;
    }
}
