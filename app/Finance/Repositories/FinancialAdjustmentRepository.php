<?php

declare(strict_types=1);

namespace App\Finance\Repositories;

use PDO;

final class FinancialAdjustmentRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function findTransactionForUpdate(int $transactionId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id_transacao, valor, payment_method, payment_date
             FROM transacoes
             WHERE id_transacao = :id
             FOR UPDATE'
        );
        $statement->execute(['id' => $transactionId]);
        $transaction = $statement->fetch(PDO::FETCH_ASSOC);

        return $transaction === false ? null : $transaction;
    }

    public function totalAdjusted(int $transactionId): float
    {
        $statement = $this->connection->prepare(
            'SELECT COALESCE(SUM(amount), 0)
             FROM financial_adjustments
             WHERE transaction_id = :transaction_id'
        );
        $statement->execute(['transaction_id' => $transactionId]);

        return (float) $statement->fetchColumn();
    }

    public function create(
        int $transactionId,
        string $type,
        float $amount,
        string $reason,
        int $createdBy
    ): int {
        $statement = $this->connection->prepare(
            'INSERT INTO financial_adjustments (
                transaction_id, adjustment_type, amount, reason, created_by
             ) VALUES (
                :transaction_id, :type, :amount, :reason, :created_by
             )'
        );
        $statement->execute([
            'transaction_id' => $transactionId,
            'type' => $type,
            'amount' => $amount,
            'reason' => $reason,
            'created_by' => $createdBy,
        ]);

        return (int) $this->connection->lastInsertId();
    }
}
