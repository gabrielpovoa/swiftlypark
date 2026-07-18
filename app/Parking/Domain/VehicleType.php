<?php

declare(strict_types=1);

namespace App\Parking\Domain;

use DomainException;

enum VehicleType: string
{
    case Car = 'carro';
    case Motorcycle = 'moto';
    case Truck = 'caminhao';
    case AppDriver = 'app';

    public static function fromInput(string $value): self
    {
        return self::tryFrom(strtolower(trim($value)))
            ?? throw new DomainException('Tipo de veículo inválido.');
    }
}
