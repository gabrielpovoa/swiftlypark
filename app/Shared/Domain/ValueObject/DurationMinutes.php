<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use DomainException;

final class DurationMinutes
{
    public function __construct(private readonly int $value)
    {
        if ($value < 0) { throw new DomainException('A duração não pode ser negativa.'); }
    }
    public function value(): int { return $this->value; }
}
