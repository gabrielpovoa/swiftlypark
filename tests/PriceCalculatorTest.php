<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Billing\Infrastructure\PdoPricingRepository;
use App\Billing\Application\PriceCalculator;

$connection = new PDO('sqlite::memory:');
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$connection->exec(
    'CREATE TABLE tarifarios (
        company_id INTEGER NOT NULL,
        tipo_veiculo TEXT NOT NULL,
        valor_base NUMERIC NOT NULL,
        valor_adicional NUMERIC NOT NULL,
        tolerancia_minutos INTEGER NOT NULL,
        frequencia_adicional INTEGER NOT NULL
    )'
);
$connection->exec(
    "INSERT INTO tarifarios VALUES (7, 'carro', 10.00, 3.50, 15, 30)"
);

$calculator = new PriceCalculator(new PdoPricingRepository($connection));

$assertAmount = static function (float $expected, float $actual, string $scenario): void {
    if (abs($expected - $actual) > 0.001) {
        throw new RuntimeException(
            sprintf('%s: expected %.2f, got %.2f', $scenario, $expected, $actual)
        );
    }
};

$assertAmount(10.00, $calculator->calculate(7, 'carro', 15), 'base period');
$assertAmount(13.50, $calculator->calculate(7, 'carro', 16), 'first additional period');
$assertAmount(13.50, $calculator->calculate(7, 'carro', 45), 'complete additional period');
$assertAmount(17.00, $calculator->calculate(7, 'carro', 46), 'second additional period');

try {
    $calculator->calculate(7, 'moto', 30);
    throw new RuntimeException('Missing tariff should block checkout.');
} catch (DomainException) {
}

echo "Price calculator test passed\n";
