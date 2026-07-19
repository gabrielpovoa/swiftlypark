<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Finance\Domain\FinancialScope;

$global = FinancialScope::global();
if (!$global->isGlobal() || $global->companyId() !== null) {
    throw new RuntimeException('Escopo financeiro global inválido.');
}

$company = FinancialScope::company(5);
if ($company->isGlobal() || $company->companyId() !== 5) {
    throw new RuntimeException('Escopo financeiro empresarial inválido.');
}

try {
    FinancialScope::company(0);
    throw new RuntimeException('Escopo aceitou empresa inválida.');
} catch (DomainException) {
}

echo "Financial scope test passed\n";
