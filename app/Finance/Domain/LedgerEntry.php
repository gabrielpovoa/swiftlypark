<?php

declare(strict_types=1);

namespace App\Finance\Domain;

use App\Shared\Domain\ValueObject\Money;
use DateTimeImmutable;
use DomainException;

final class LedgerEntry
{
    private function __construct(
        public readonly int $companyId,
        public readonly string $sourceType,
        public readonly int $sourceId,
        public readonly string $entryType,
        public readonly Money $amount,
        public readonly DateTimeImmutable $occurredAt,
        public readonly string $description,
        public readonly ?int $createdBy
    ) {}

    public static function credit(int $companyId, string $sourceType, int $sourceId, Money $amount,
        DateTimeImmutable $occurredAt, string $description, ?int $createdBy): self
    {
        return self::create($companyId, $sourceType, $sourceId, 'CREDIT', $amount, $occurredAt, $description, $createdBy);
    }

    public static function debit(int $companyId, string $sourceType, int $sourceId, Money $amount,
        DateTimeImmutable $occurredAt, string $description, ?int $createdBy): self
    {
        return self::create($companyId, $sourceType, $sourceId, 'DEBIT', $amount, $occurredAt, $description, $createdBy);
    }

    private static function create(int $companyId, string $sourceType, int $sourceId, string $entryType,
        Money $amount, DateTimeImmutable $occurredAt, string $description, ?int $createdBy): self
    {
        if ($companyId < 1 || $sourceId < 1 || $amount->cents() < 1
            || !in_array($sourceType, ['ROTATING_PAYMENT', 'MONTHLY_PAYMENT', 'ADJUSTMENT'], true)) {
            throw new DomainException('Lançamento financeiro inválido.');
        }
        $description = strip_tags($description);
        $description = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $description) ?? '';
        return new self($companyId, $sourceType, $sourceId, $entryType, $amount, $occurredAt,
            mb_substr(trim($description), 0, 500), $createdBy);
    }
}
