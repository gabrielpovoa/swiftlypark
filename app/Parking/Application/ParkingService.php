<?php

declare(strict_types=1);

namespace App\Parking\Application;

use App\Parking\Infrastructure\PdoParkingGateway;

final class ParkingService
{
    public function __construct(private ?PdoParkingGateway $gateway = null)
    {
        $this->gateway ??= new PdoParkingGateway();
    }

    public function getAvailableCounts(): array { return $this->gateway->getAvailableCounts(); }
    public function getFreeVagaByCategory(string $category): array|false { return $this->gateway->getFreeVagaByCategory($category); }
    public function getVacancyByType(string $category): array { return $this->gateway->getVacancyByType($category); }
    public function getVagaById(int $id): array|false { return $this->gateway->getVagaById($id); }
    public function isMonthlyCompany(): bool { return $this->gateway->isMonthlyCompany(); }
    public function ocuparVaga(int $id, string $entry, string $owner, string $phone, string $plate, string $type): int
    {
        return $this->gateway->ocuparVaga($id, $entry, $owner, $phone, $plate, $type);
    }
    public function checkIfVehicleIsParked(string $plate): mixed { return $this->gateway->checkIfVehicleIsParked($plate); }
    public function getVagasFiltradas(?string $category = null, ?string $plate = null): array
    {
        return $this->gateway->getVagasFiltradas($category, $plate);
    }
    public function finalizarVaga(int $id, string $exit, ?string $paymentMethod): array
    {
        return $this->gateway->finalizarVaga($id, $exit, $paymentMethod);
    }
}
