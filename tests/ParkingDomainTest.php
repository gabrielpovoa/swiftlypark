<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Parking\Domain\BillingMode;
use App\Parking\Domain\VacancyStatus;
use App\Parking\Domain\VehicleType;

if (VehicleType::fromInput(' CAMINHAO ') !== VehicleType::Truck) {
    throw new RuntimeException('Tipo de veículo não foi normalizado.');
}
if (BillingMode::Monthly->value !== 'MONTHLY' || VacancyStatus::Free->value !== 'livre') {
    throw new RuntimeException('Estados persistidos de Parking foram alterados.');
}
try {
    VehicleType::fromInput('aviao');
    throw new RuntimeException('Tipo inválido foi aceito.');
} catch (DomainException) {
}
echo "Parking domain test passed\n";
