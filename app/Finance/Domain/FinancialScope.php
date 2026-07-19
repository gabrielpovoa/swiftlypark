<?php

declare(strict_types=1);

namespace App\Finance\Domain;

use DomainException;

final class FinancialScope
{
    private function __construct(private readonly ?int $companyId)
    {
    }

    public static function global(): self
    {
        return new self(null);
    }

    public static function company(int $companyId): self
    {
        if ($companyId < 1) {
            throw new DomainException('Empresa inválida para o relatório financeiro.');
        }

        return new self($companyId);
    }

    public function isGlobal(): bool
    {
        return $this->companyId === null;
    }

    public function companyId(): ?int
    {
        return $this->companyId;
    }
}
