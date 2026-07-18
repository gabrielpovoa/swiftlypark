<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Context\TenantContext;
use App\Companies\Domain\Company;

$context = TenantContext::instance();
$context->clear();
$company = Company::reconstitute(42, 'Acme Parking', 'acme-parking', null, true);

$context->setCompany($company);

if ($context->getCompanyId() !== 42) {
    fwrite(STDERR, "Expected company id 42\n");
    exit(1);
}

if ($context->getCompany()?->slug() !== 'acme-parking') {
    fwrite(STDERR, "Expected company slug to be acme-parking\n");
    exit(1);
}

echo "TenantContext test passed\n";
