<?php

declare(strict_types=1);

namespace App\Finance\Infrastructure;

use PDO;

final class FinancialAdjustmentRepository extends \App\Repositories\BaseRepository
{

    public function findTransactionForUpdate(int $transactionId): ?array
    {
        $parameters = ['id' => $transactionId];
        $query = 'SELECT id_transacao, valor, payment_method, payment_date
             FROM transacoes
             WHERE id_transacao = :id
             FOR UPDATE';
        $this->applyTenantFilter($query, $parameters, 'company_id');

        $statement = $this->prepareTenantStatement($query, $parameters);
        $statement->execute($parameters);
        $transaction = $statement->fetch(PDO::FETCH_ASSOC);

        return $transaction === false ? null : $transaction;
    }

    public function totalAdjusted(int $transactionId): float
    {
        $parameters = ['transaction_id' => $transactionId];
        $query = 'SELECT COALESCE(SUM(amount), 0)
             FROM financial_adjustments
             WHERE transaction_id = :transaction_id';
        $this->applyTenantFilter($query, $parameters, 'company_id');

        $statement = $this->prepareTenantStatement($query, $parameters);
        $statement->execute($parameters);

        return (float) $statement->fetchColumn();
    }

    public function create(
        int $transactionId,
        string $type,
        float $amount,
        string $reason,
        int $createdBy
    ): int {
        $companyId = $this->assertTenantContext();

        $query = 'INSERT INTO financial_adjustments (
                company_id, transaction_id, adjustment_type, amount, reason, created_by
             ) VALUES (
                :company_id, :transaction_id, :type, :amount, :reason, :created_by
             )';
        $parameters = [
            'company_id' => $companyId,
            'transaction_id' => $transactionId,
            'type' => $type,
            'amount' => $amount,
            'reason' => $reason,
            'created_by' => $createdBy,
        ];

        $statement = $this->prepareTenantStatement($query, $parameters);
        $statement->execute($parameters);

        return (int) $this->connection->lastInsertId();
    }
}

