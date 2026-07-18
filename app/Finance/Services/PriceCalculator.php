<?php

declare(strict_types=1);

namespace App\Finance\Services;

use App\Finance\Repositories\PricingRepository;
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

        $frequency = (int) $tariff['frequencia_adicional'];
        $tolerance = max(0, (int) $tariff['tolerancia_minutos']);
        if ($frequency < 1) {
            throw new DomainException('O tarifário possui frequência adicional inválida.');
        }

        $baseCents = $this->decimalToCents((string) $tariff['valor_base']);
        $additionalCents = $this->decimalToCents((string) $tariff['valor_adicional']);
        $chargeableMinutes = max(0, $durationMinutes - $tolerance);
        $additionalPeriods = (int) ceil($chargeableMinutes / $frequency);
        $totalCents = $baseCents + ($additionalPeriods * $additionalCents);

        return [
            'company_id' => $companyId,
            'vehicle_type' => $vehicleType,
            'duration_minutes' => $durationMinutes,
            'base_amount' => $baseCents / 100,
            'additional_amount' => $additionalCents / 100,
            'tolerance_minutes' => $tolerance,
            'additional_frequency' => $frequency,
            'additional_periods' => $additionalPeriods,
            'total' => $totalCents / 100,
        ];
    }

    private function decimalToCents(string $amount): int
    {
        if (preg_match('/^\d+(?:\.\d{1,2})?$/', $amount) !== 1) {
            throw new DomainException('O tarifário possui um valor monetário inválido.');
        }

        [$integer, $decimal] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $integer * 100) + (int) str_pad($decimal, 2, '0');
    }
}
