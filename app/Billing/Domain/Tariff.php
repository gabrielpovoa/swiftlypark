<?php

declare(strict_types=1);

namespace App\Billing\Domain;

use DomainException;
use App\Shared\Domain\ValueObject\DurationMinutes;
use App\Shared\Domain\ValueObject\Money;

final class Tariff
{
    private function __construct(
        private readonly int $companyId,
        private readonly string $vehicleType,
        private readonly Money $base,
        private readonly Money $additional,
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
        return new self($companyId, $type, Money::fromDecimal((string) $row['valor_base']),
            Money::fromDecimal((string) $row['valor_adicional']), $tolerance, $frequency);
    }

    public function calculate(int $durationMinutes): array
    {
        $durationMinutes = (new DurationMinutes($durationMinutes))->value();
        $chargeable = max(0, $durationMinutes - $this->toleranceMinutes);
        $periods = (int) ceil($chargeable / $this->additionalFrequency);
        $total = $this->base->add($this->additional->multiply($periods));
        return [
            'company_id' => $this->companyId, 'vehicle_type' => $this->vehicleType,
            'duration_minutes' => $durationMinutes, 'base_amount' => $this->base->toFloat(),
            'additional_amount' => $this->additional->toFloat(),
            'tolerance_minutes' => $this->toleranceMinutes,
            'additional_frequency' => $this->additionalFrequency,
            'additional_periods' => $periods, 'total' => $total->toFloat(),
        ];
    }
}
