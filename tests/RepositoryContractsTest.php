<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Billing\Application\PriceCalculator;
use App\Billing\Domain\PricingRepository;
use App\Parking\Application\ParkingService;
use App\Parking\Domain\ParkingGateway;

$pricing = new class implements PricingRepository {
    public function findTariff(int $companyId, string $vehicleType): ?array
    {
        return ['company_id' => $companyId, 'tipo_veiculo' => $vehicleType,
            'valor_base' => '10.00', 'valor_adicional' => '2.00',
            'tolerancia_minutos' => 10, 'frequencia_adicional' => 30];
    }
    public function isMonthlyCompany(int $companyId): bool { return false; }
};
if ((new PriceCalculator($pricing))->calculate(1, 'carro', 41) !== 14.0) {
    throw new RuntimeException('PriceCalculator não operou com repository fake.');
}

$parkingGateway = new class implements ParkingGateway {
    public function getAvailableCounts() { return ['carro' => 3]; }
    public function getFreeVagaByCategory(string $category) { return false; }
    public function getVacancyByType(string $category) { return []; }
    public function getVagaById(int $id) { return false; }
    public function isMonthlyCompany(): bool { return false; }
    public function ocuparVaga(int $id, string $entry, string $owner, string $phone, string $plate, string $type): int { return 99; }
    public function checkIfVehicleIsParked(string $plate) { return false; }
    public function getVagasFiltradas(?string $category = null, ?string $plate = null) { return []; }
    public function finalizarVaga(int $id, string $exit, ?string $paymentMethod): array { return ['monthly' => false]; }
};
if ((new ParkingService($parkingGateway))->getAvailableCounts() !== ['carro' => 3]) {
    throw new RuntimeException('ParkingService não operou com gateway fake.');
}

echo "Repository contracts test passed\n";
