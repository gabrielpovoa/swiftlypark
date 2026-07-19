<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$routes = file_get_contents($root . '/routes/web.php');
$identityMiddleware = file_get_contents($root . '/app/Middleware/IdentityMiddleware.php');
$dashboard = file_get_contents($root . '/app/Controllers/DashboardGlobalController.php');
$migration = file_get_contents(
    $root . '/database/migrations/20260729_scope_platform_and_tenant_roles.sql'
);

if (in_array(false, [$routes, $identityMiddleware, $dashboard, $migration], true)) {
    throw new RuntimeException('Não foi possível carregar os arquivos do escopo administrativo.');
}

if (!str_contains($routes, "get('admin/dashboard', adminDashboardRequired")) {
    throw new RuntimeException('O dashboard administrativo deve aceitar autorização global ou por tenant.');
}

if (!str_contains($routes, "return in_array('super-admin', \$roles, true);")
    || str_contains($routes, "|| in_array('master', \$roles, true)")) {
    throw new RuntimeException('Somente super-admin pode ser reconhecido como administrador global.');
}

if (!str_contains($dashboard, '(new HomeController())->index()')) {
    throw new RuntimeException('Usuários empresariais devem receber o dashboard do tenant.');
}

if (!str_contains($identityMiddleware, '$this->isGlobalDashboardRequest() && $isPlatformAdmin')) {
    throw new RuntimeException('O dashboard só pode remover o tenant para um administrador da plataforma.');
}

if (!str_contains($migration, "assigned_role.slug <> 'super-admin'")) {
    throw new RuntimeException('A migração deve preservar exclusivamente o papel global super-admin.');
}

echo "Admin dashboard tenant scope test passed\n";
