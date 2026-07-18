<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$routes = file_get_contents($root . '/routes/web.php');
$legacy = file_get_contents($root . '/app/Controllers/AdminProvisioningController.php');
$monthly = file_get_contents($root . '/app/Controllers/AdminMonthlyContractController.php');

if ($routes === false || $legacy === false || $monthly === false) {
    throw new RuntimeException('Não foi possível carregar os arquivos administrativos.');
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
    if (str_contains($legacy, 'function ' . $legacyMethod . '(')) {
        throw new RuntimeException('Responsabilidade mensalista retornou ao controller legado.');
    }
}

foreach (['companyPricing', 'updateCompanyPricing', 'validateTariff'] as $legacyMethod) {
    if (str_contains($legacy, 'function ' . $legacyMethod . '(')) {
        throw new RuntimeException('Responsabilidade de cobrança retornou ao controller legado.');
    }
}

foreach (['beginTransaction()', 'commit()', 'rollBack()', 'FOR UPDATE', 'AuditLogRepository'] as $guarantee) {
    if (!str_contains($monthly, $guarantee)) {
        throw new RuntimeException('Garantia ausente no controller mensalista: ' . $guarantee);
    }
}

echo "Admin controller boundary test passed\n";
