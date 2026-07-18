<?php
declare(strict_types=1);
namespace App\Finance\Domain;
interface FinancialAdjustmentRepository
{
    public function findTransactionForUpdate(int $transactionId): ?array;
    public function totalAdjusted(int $transactionId): float;
    public function create(int $transactionId, string $type, float $amount, string $reason, int $userId): int;
}
