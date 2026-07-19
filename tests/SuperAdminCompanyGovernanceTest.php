<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$routes = file_get_contents($root . '/routes/web.php');
$navigation = file_get_contents($root . '/app/Authorization/Services/NavigationService.php');
$migration = file_get_contents($root . '/database/migrations/20260730_remove_master_role.sql');
$provisioning = file_get_contents($root . '/app/Identity/Application/UserProvisioningService.php');

if (in_array(false, [$routes, $navigation, $migration, $provisioning], true)) {
    throw new RuntimeException('Não foi possível carregar os arquivos de governança de empresas.');
}

$companyRoutes = [
    "get('admin/companies', companyGovernanceRequired",
    "get('admin/companies/pricing', companyGovernanceRequired",
    "get('admin/companies/company_id={company_id}', companyGovernanceRequired",
    "post('admin/companies/update', companyGovernanceRequired",
    "post('admin/companies/pricing', companyGovernanceRequired",
    "post('admin/companies/company_id={company_id}', companyGovernanceRequired",
    "post('admin/companies/company_id={company_id}/contracts/create', companyGovernanceRequired",
    "post('admin/companies/company_id={company_id}/contracts/renew', companyGovernanceRequired",
    "post('admin/companies/company_id={company_id}/contracts/cancel', companyGovernanceRequired",
];

foreach ($companyRoutes as $route) {
    if (!str_contains($routes, $route)) {
        throw new RuntimeException("Rota sem proteção de governança por empresa: {$route}");
    }
}

foreach ([
    "post('admin/companies/create', superAdminRequired",
    "post('admin/companies/deactivate', superAdminRequired",
] as $globalOnlyRoute) {
    if (!str_contains($routes, $globalOnlyRoute)) {
        throw new RuntimeException("Operação global sem proteção de super-admin: {$globalOnlyRoute}");
    }
}

if (!str_contains($navigation, "'roles' => ['admin', 'super-admin']")) {
    throw new RuntimeException('O menu da empresa deve aparecer para admin e super-admin.');
}

foreach (['UPDATE company_user', 'DELETE FROM user_roles', 'DELETE FROM role_permissions', 'DELETE FROM roles'] as $sql) {
    if (!str_contains($migration, $sql)) {
        throw new RuntimeException("Migração de remoção do master incompleta: {$sql}");
    }
}

if (str_contains($provisioning, "['master', 'super-admin']")) {
    throw new RuntimeException('Provisionamento ainda reconhece master como papel privilegiado.');
}

echo "Super-admin company governance test passed\n";
