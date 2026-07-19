<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$controller = file_get_contents($root . '/app/Finance/Presentation/FinanceController.php');
$tenantController = file_get_contents($root . '/app/Controllers/ApiTenantController.php');
$routes = file_get_contents($root . '/routes/web.php');
$javascript = file_get_contents($root . '/public/js/TenantContext.js');

if (in_array(false, [$controller, $tenantController, $routes, $javascript], true)) {
    throw new RuntimeException('Não foi possível carregar o fluxo de contexto financeiro.');
}

foreach ([
    "array_key_exists('company_id', \$_GET)",
    "\$_SESSION['support_impersonation']['company_id']",
] as $marker) {
    if (!str_contains($controller, $marker)) {
        throw new RuntimeException('Precedência financeira ausente: ' . $marker);
    }
}

if (!str_contains($tenantController, 'function switchGlobal()')
    || !str_contains($routes, 'api/v1/tenant/global')
    || !str_contains($javascript, "fetch('/api/v1/tenant/global'")) {
    throw new RuntimeException('Troca autenticada para contexto global ausente.');
}

echo "Finance context precedence test passed\n";
