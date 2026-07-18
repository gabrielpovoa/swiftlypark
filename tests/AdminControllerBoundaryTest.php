<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$routes = file_get_contents($root . '/routes/web.php');
$legacyPath = $root . '/app/Controllers/AdminProvisioningController.php';
$users = file_get_contents($root . '/app/Identity/Presentation/AdminUserProvisioningController.php');
$companies = file_get_contents($root . '/app/Controllers/AdminCompanyController.php');
$monthly = file_get_contents($root . '/app/Controllers/AdminMonthlyContractController.php');

if ($routes === false || $users === false || $companies === false || $monthly === false) {
    throw new RuntimeException('Não foi possível carregar os arquivos administrativos.');
}

if (is_file($legacyPath)) {
    throw new RuntimeException('O controller administrativo legado voltou a existir.');
}

foreach (['create', 'renew', 'cancel'] as $action) {
    $expected = '(new AdminMonthlyContractController())->' . $action . '($companyId);';
    if (!str_contains($routes, $expected)) {
        throw new RuntimeException('Rota mensalista não aponta para ' . $action . '.');
    }
}

foreach (['show', 'update'] as $action) {
    $expected = '(new AdminCompanyBillingController())->' . $action;
    if (!str_contains($routes, $expected)) {
        throw new RuntimeException('Rota de cobrança não aponta para ' . $action . '.');
    }
}

foreach (['createMonthlyContract', 'renewMonthlyContract', 'cancelMonthlyContract'] as $legacyMethod) {
    if (str_contains($users, 'function ' . $legacyMethod . '(')) {
        throw new RuntimeException('Responsabilidade mensalista retornou ao controller legado.');
    }
}

foreach (['companyPricing', 'updateCompanyPricing', 'validateTariff'] as $legacyMethod) {
    if (str_contains($users, 'function ' . $legacyMethod . '(')) {
        throw new RuntimeException('Responsabilidade de cobrança retornou ao controller legado.');
    }
}

foreach (['createCompany', 'companiesIndex', 'updateCompany', 'deactivateCompany'] as $action) {
    if (!str_contains($companies, 'function ' . $action . '(')) {
        throw new RuntimeException('Ação ausente no controller de empresas: ' . $action);
    }
    if (str_contains($users, 'function ' . $action . '(')) {
        throw new RuntimeException('Governança de empresas presente no controller de usuários.');
    }
}

foreach (['beginTransaction()', 'commit()', 'rollBack()', 'FOR UPDATE', 'AuditLogRepository'] as $guarantee) {
    if (!str_contains($monthly, $guarantee)) {
        throw new RuntimeException('Garantia ausente no controller mensalista: ' . $guarantee);
    }
}

echo "Admin controller boundary test passed\n";
