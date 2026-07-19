<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$directory = file_get_contents($root . '/app/Views/Admin/companies.php');
$company = file_get_contents($root . '/app/Views/Admin/company-pricing.php');
$navigation = file_get_contents($root . '/app/Authorization/Services/NavigationService.php');
$billing = file_get_contents(
    $root . '/app/Billing/Presentation/AdminCompanyBillingController.php'
);
$users = file_get_contents(
    $root . '/app/Identity/Presentation/AdminUserProvisioningController.php'
);

if (in_array(false, [$directory, $company, $navigation, $billing, $users], true)) {
    throw new RuntimeException('Não foi possível carregar os arquivos de gestão de empresas.');
}

foreach (['/admin/companies/create', 'data-company-logo-input'] as $expected) {
    if (!str_contains($directory, $expected)) {
        throw new RuntimeException('Diretório sem recurso de criação: ' . $expected);
    }
}

foreach (['/admin/users/link-company', '/admin/users/remove-company', 'return_company_id'] as $expected) {
    if (!str_contains($company, $expected)) {
        throw new RuntimeException('Página da empresa sem gestão de vínculo: ' . $expected);
    }
}

if (!str_contains($navigation, "'href' => '/admin/companies'")) {
    throw new RuntimeException('Menu Empresas ausente da navegação autorizada.');
}

if (str_contains($billing, 'SET name = :trade_name')
    || !str_contains($billing, 'SET name = :company_name')) {
    throw new RuntimeException('UPDATE cadastral pode reutilizar parâmetro PDO nomeado.');
}

if (!str_contains($users, "\$_POST['return_company_id']")) {
    throw new RuntimeException('Ação de vínculo não retorna à empresa de origem.');
}

echo "Company management view test passed\n";
