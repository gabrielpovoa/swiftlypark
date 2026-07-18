<?php

declare(strict_types=1);

$root = dirname(__DIR__);
foreach (['Application', 'Domain', 'Infrastructure', 'Presentation'] as $layer) {
    if (!is_dir($root . '/app/Finance/' . $layer)) {
        throw new RuntimeException('Camada Finance ausente: ' . $layer);
    }
}
foreach (['Services', 'Repositories'] as $legacy) {
    if (is_dir($root . '/app/Finance/' . $legacy)) {
        throw new RuntimeException('Diretório legado em Finance: ' . $legacy);
    }
}
foreach (glob($root . '/app/Finance/Domain/*.php') ?: [] as $file) {
    $source = (string) file_get_contents($file);
    if (str_contains($source, 'PDO') || str_contains($source, 'Infrastructure')) {
        throw new RuntimeException('Domain Finance depende de infraestrutura.');
    }
}
$routes = (string) file_get_contents($root . '/routes/web.php');
if (!str_contains($routes, 'App\\Finance\\Presentation\\FinanceController')) {
    throw new RuntimeException('Rotas não usam Presentation de Finance.');
}
foreach (['PricingRepository', 'MonthlyContractRepository', 'PriceCalculator'] as $billing) {
    foreach (glob($root . '/app/Finance/*/*.php') ?: [] as $file) {
        if (str_contains(basename($file), $billing)) {
            throw new RuntimeException('Billing voltou para Finance: ' . $billing);
        }
    }
}
echo "Finance module boundary test passed\n";
