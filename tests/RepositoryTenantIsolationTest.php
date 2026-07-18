<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Context\TenantContext;
use App\Exceptions\SecurityCriticalException;
use App\Repositories\BaseRepository;

final class TestRepository extends BaseRepository
{
    public function runQuery(): array
    {
        $query = 'SELECT 1 AS value';
        $parameters = [];
        $this->applyTenantFilter($query, $parameters);

        return ['query' => $query, 'parameters' => $parameters];
    }

    public function runOrderedQuery(): array
    {
        $query = 'SELECT id FROM audit_logs al ORDER BY al.created_at DESC LIMIT 10';
        $parameters = [];
        $this->applyTenantFilter($query, $parameters, 'al.company_id');

        return ['query' => $query, 'parameters' => $parameters];
    }
}

$tenantContext = TenantContext::instance();
$tenantContext->clear();

try {
    (new TestRepository($tenantContext, new PDO('sqlite::memory:')))->runQuery();
    fwrite(STDERR, "Expected SecurityCriticalException\n");
    exit(1);
} catch (SecurityCriticalException $exception) {
    if ($exception->securityCode() !== SecurityCriticalException::CODE_TENANT_CONTEXT_MISSING) {
        fwrite(STDERR, "Expected tenant context security code\n");
        exit(1);
    }
}

$tenantContext->setCompany(\App\Companies\Domain\Company::reconstitute(
    7,
    'Tenant Seven',
    'tenant-seven',
    null,
    true
));
$result = (new TestRepository($tenantContext, new PDO('sqlite::memory:')))->runOrderedQuery();

if ($result['query'] !== 'SELECT id FROM audit_logs al WHERE al.company_id = :company_id ORDER BY al.created_at DESC LIMIT 10') {
    fwrite(STDERR, "Tenant filter was not inserted before ORDER BY\n");
    exit(1);
}

if (($result['parameters']['company_id'] ?? null) !== 7) {
    fwrite(STDERR, "Expected tenant company id 7\n");
    exit(1);
}

$tenantContext->clear();

echo "Repository tenant isolation test passed\n";
