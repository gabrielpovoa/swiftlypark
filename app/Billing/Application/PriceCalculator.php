<?php

declare(strict_types=1);

namespace App\Billing\Application;

use App\Billing\Domain\PricingRepository;
use App\Billing\Domain\Tariff;
use DomainException;

final class PriceCalculator
{
    private const VEHICLE_TYPES = ['carro', 'moto', 'caminhao', 'app'];

    public function __construct(private PricingRepository $pricing)
    {
    }

    public function calculate(int $companyId, string $vehicleType, int $durationMinutes): float
    {
        return $this->calculateDetails($companyId, $vehicleType, $durationMinutes)['total'];
    }

    public function calculateDetails(
        int $companyId,
        string $vehicleType,
        int $durationMinutes
    ): array {
        $vehicleType = strtolower(trim($vehicleType));
        if ($companyId < 1 || $durationMinutes < 0 || !in_array($vehicleType, self::VEHICLE_TYPES, true)) {
            throw new DomainException('Dados inválidos para o cálculo da tarifa.');
        }

        $tariff = $this->pricing->findTariff($companyId, $vehicleType);
        if ($tariff === null) {
            throw new DomainException('Não existe tarifário configurado para este tipo de veículo.');
        }

        return Tariff::fromPersistence($tariff)->calculate($durationMinutes);
    }
}
