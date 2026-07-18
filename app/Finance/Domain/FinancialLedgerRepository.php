<?php
declare(strict_types=1);
namespace App\Finance\Domain;
interface FinancialLedgerRepository
{
    public function append(LedgerEntry $entry): int;

    public function balance(int $companyId, string $startUtc, string $endUtc): array;

    public function reconciliation(int $companyId): array;
}
