<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use DomainException;

final class Money
{
    private function __construct(private readonly int $cents) {}

    public static function fromCents(int $cents): self
    {
        if ($cents < 0) { throw new DomainException('O valor monetário não pode ser negativo.'); }
        return new self($cents);
    }

    public static function fromDecimal(string $amount): self
    {
        $amount = trim($amount);
        if (preg_match('/^\d+(?:\.\d{1,2})?$/', $amount) !== 1) {
            throw new DomainException('Valor monetário inválido.');
        }
        [$integer, $decimal] = array_pad(explode('.', $amount, 2), 2, '');
        return new self(((int) $integer * 100) + (int) str_pad($decimal, 2, '0'));
    }

    public function add(self $other): self { return new self($this->cents + $other->cents); }
    public function multiply(int $quantity): self
    {
        if ($quantity < 0) { throw new DomainException('Multiplicador monetário inválido.'); }
        return new self($this->cents * $quantity);
    }
    public function cents(): int { return $this->cents; }
    public function toFloat(): float { return $this->cents / 100; }
    public function decimal(): string { return number_format($this->cents / 100, 2, '.', ''); }
    public function equals(self $other): bool { return $this->cents === $other->cents; }
}
