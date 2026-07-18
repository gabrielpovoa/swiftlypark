<?php

declare(strict_types=1);

$root = dirname(__DIR__);
foreach (['Application', 'Domain', 'Infrastructure', 'Presentation'] as $layer) {
    if (!is_dir($root . '/app/Billing/' . $layer)) {
        throw new RuntimeException('Camada Billing ausente: ' . $layer);
    }
}
foreach (glob($root . '/app/Billing/Domain/*.php') ?: [] as $file) {
    $source = (string) file_get_contents($file);
    if (str_contains($source, 'Infrastructure') || str_contains($source, 'PDO')) {
        throw new RuntimeException('Domain Billing depende de infraestrutura.');
    }
}
foreach (['PriceCalculator.php', 'PricingRepository.php', 'MonthlyContractRepository.php'] as $legacy) {
    if (is_file($root . '/app/Finance/Services/' . $legacy)
        || is_file($root . '/app/Finance/Repositories/' . $legacy)) {
        throw new RuntimeException('Regra de Billing ainda está em Finance: ' . $legacy);
    }
}
$parking = (string) file_get_contents($root . '/app/Parking/Infrastructure/PdoParkingGateway.php');
if (!str_contains($parking, 'App\\Billing\\Application\\PriceCalculator')) {
    throw new RuntimeException('Parking não consome a fronteira de cálculo Billing.');
}
echo "Billing module boundary test passed\n";
