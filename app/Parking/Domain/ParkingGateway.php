<?php
declare(strict_types=1);
namespace App\Parking\Domain;
interface ParkingGateway
{
    public function getAvailableCounts();
    public function getFreeVagaByCategory(string $category);
    public function getVacancyByType(string $category);
    public function getVagaById(int $id);
    public function isMonthlyCompany(): bool;
    public function ocuparVaga(int $id, string $entry, string $owner, string $phone, string $plate, string $type): int;
    public function checkIfVehicleIsParked(string $plate);
    public function getVagasFiltradas(?string $category = null, ?string $plate = null);
    public function finalizarVaga(int $id, string $exit, ?string $paymentMethod): array;
}
