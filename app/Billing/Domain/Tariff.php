<?php

declare(strict_types=1);

namespace App\Billing\Domain;

use DomainException;

final class Tariff
{
    private function __construct(
        private readonly int $companyId,
        private readonly string $vehicleType,
        private readonly int $baseCents,
        private readonly int $additionalCents,
        private readonly int $toleranceMinutes,
        private readonly int $additionalFrequency
    ) {}

    public static function fromPersistence(array $row): self
    {
        $companyId = (int) ($row['company_id'] ?? 0);
        $type = strtolower(trim((string) ($row['tipo_veiculo'] ?? '')));
        $frequency = (int) ($row['frequencia_adicional'] ?? 0);
        $tolerance = max(0, (int) ($row['tolerancia_minutos'] ?? 0));
        if ($companyId < 1 || !in_array($type, ['carro', 'moto', 'caminhao', 'app'], true)
            || $frequency < 1) {
            throw new DomainException('O tarifário possui configuração inválida.');
        }
        return new self($companyId, $type, self::toCents((string) $row['valor_base']),
            self::toCents((string) $row['valor_adicional']), $tolerance, $frequency);
    }

    public function calculate(int $durationMinutes): array
    {
        if ($durationMinutes < 0) {
            throw new DomainException('A duração não pode ser negativa.');
        }
        $chargeable = max(0, $durationMinutes - $this->toleranceMinutes);
        $periods = (int) ceil($chargeable / $this->additionalFrequency);
        $total = $this->baseCents + ($periods * $this->additionalCents);
        return [
            'company_id' => $this->companyId, 'vehicle_type' => $this->vehicleType,
            'duration_minutes' => $durationMinutes, 'base_amount' => $this->baseCents / 100,
            'additional_amount' => $this->additionalCents / 100,
            'tolerance_minutes' => $this->toleranceMinutes,
            'additional_frequency' => $this->additionalFrequency,
            'additional_periods' => $periods, 'total' => $total / 100,
        ];
    }

    private static function toCents(string $amount): int
    {
        if (preg_match('/^\d+(?:\.\d{1,2})?$/', $amount) !== 1) {
            throw new DomainException('O tarifário possui um valor monetário inválido.');
        }
        [$integer, $decimal] = array_pad(explode('.', $amount, 2), 2, '');
        return ((int) $integer * 100) + (int) str_pad($decimal, 2, '0');
    }
}
