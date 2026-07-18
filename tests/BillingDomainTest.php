<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Billing\Domain\Tariff;

$tariff = Tariff::fromPersistence([
    'company_id' => 5, 'tipo_veiculo' => 'caminhao', 'valor_base' => '25.00',
    'valor_adicional' => '7.28', 'tolerancia_minutos' => 15,
    'frequencia_adicional' => 60,
]);
$details = $tariff->calculate(113);
if ($details['additional_periods'] !== 2 || abs($details['total'] - 39.56) > 0.001) {
    throw new RuntimeException('Cálculo de domínio do tarifário divergiu: ' . json_encode($details));
}
if (abs((float) $tariff->calculate(15)['total'] - 25.0) > 0.001) {
    throw new RuntimeException('Tolerância do tarifário não foi respeitada.');
}
echo "Billing domain test passed\n";
