<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use DomainException;

final class VehiclePlate
{
    private function __construct(private readonly string $value) {}

    public static function fromString(string $plate): self
    {
        $plate = strtoupper(trim($plate));
        $plate = preg_replace('/[^A-Z0-9-]/', '', $plate) ?? '';
        if (preg_match('/^[A-Z0-9]{3}-?[A-Z0-9]{4}$/', $plate) !== 1) {
            throw new DomainException('Placa de veículo inválida.');
        }
        return new self($plate);
    }

    public function value(): string { return $this->value; }
    public function compact(): string { return str_replace('-', '', $this->value); }
    public function equals(self $other): bool { return $this->compact() === $other->compact(); }
}
