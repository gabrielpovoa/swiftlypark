<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$migration = file_get_contents(
    $root . '/database/migrations/20260801_grant_admin_finance_view.sql'
);
$routes = file_get_contents($root . '/routes/web.php');
$controller = file_get_contents($root . '/app/Finance/Presentation/FinanceController.php');

if (in_array(false, [$migration, $routes, $controller], true)) {
    throw new RuntimeException('Não foi possível carregar o acesso financeiro administrativo.');
}

if (!str_contains($migration, "p.slug = 'finance.view'")
    || !str_contains($migration, "r.slug = 'admin'")) {
    throw new RuntimeException('Admin não recebeu finance.view formalmente.');
}

foreach (['finance', 'finance/data', 'finance/export', 'finance/print'] as $route) {
    if (!str_contains($routes, "'finance.view', '{$route}'")) {
        throw new RuntimeException("Rota financeira sem leitura protegida: {$route}");
    }
}

if (!str_contains($routes, "'finance.adjust', 'finance/refund'")
    || !str_contains($controller, "\$authorization->can('finance.adjust')")) {
    throw new RuntimeException('Estorno deve continuar separado da visão financeira.');
}

if (!str_contains($controller, 'FinancialScope::company($companyId)')) {
    throw new RuntimeException('Financeiro do admin deve permanecer no escopo da empresa.');
}

echo "Tenant admin finance overview test passed\n";
