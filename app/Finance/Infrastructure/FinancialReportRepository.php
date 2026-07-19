<?php

declare(strict_types=1);

namespace App\Finance\Infrastructure;

use App\Finance\Domain\FinancialReportRepository as FinancialReportRepositoryContract;
use App\Finance\Domain\FinancialScope;
use PDO;

final class FinancialReportRepository extends \App\Repositories\BaseRepository implements FinancialReportRepositoryContract
{
    public function __construct(PDO $connection, private readonly FinancialScope $scope)
    {
        parent::__construct($connection);
    }

    public function summary(string $startUtc, string $endUtc): array
    {
        $parameters = ['start_at' => $startUtc, 'end_at' => $endUtc];
        $companyFilter = $this->companyFilter('company_id', $parameters);
        $statement = $this->connection->prepare(
            'SELECT
             COALESCE(SUM(CASE WHEN entry_type = "CREDIT" THEN amount ELSE 0 END), 0) gross_revenue,
             COALESCE(SUM(CASE WHEN source_type = "ROTATING_PAYMENT" THEN amount ELSE 0 END), 0) rotating_revenue,
             COALESCE(SUM(CASE WHEN source_type = "MONTHLY_PAYMENT" THEN amount ELSE 0 END), 0) monthly_revenue,
             COALESCE(AVG(CASE WHEN entry_type = "CREDIT" THEN amount END), 0) average_ticket,
             SUM(CASE WHEN source_type = "ROTATING_PAYMENT" THEN 1 ELSE 0 END) rotating_transaction_count,
             SUM(CASE WHEN source_type = "MONTHLY_PAYMENT" THEN 1 ELSE 0 END) monthly_payment_count,
             SUM(CASE WHEN entry_type = "CREDIT" THEN 1 ELSE 0 END) transaction_count,
             COALESCE(SUM(CASE WHEN entry_type = "DEBIT" THEN amount ELSE 0 END), 0) adjustments
             FROM financial_ledger_entries
             WHERE occurred_at >= :start_at AND occurred_at < :end_at' . $companyFilter
        );
        $statement->execute($parameters);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function revenueByDay(string $startUtc, string $endUtc): array
    {
        $parameters = ['start_at' => $startUtc, 'end_at' => $endUtc];
        $companyFilter = $this->companyFilter('company_id', $parameters);
        $statement = $this->connection->prepare(
            "SELECT DATE(CONVERT_TZ(occurred_at, '+00:00', '-03:00')) day,
             SUM(CASE WHEN source_type = 'ROTATING_PAYMENT' THEN amount ELSE 0 END) rotating_total,
             SUM(CASE WHEN source_type = 'MONTHLY_PAYMENT' THEN amount ELSE 0 END) monthly_total,
             SUM(CASE WHEN entry_type = 'CREDIT' THEN amount ELSE 0 END) total
             FROM financial_ledger_entries
             WHERE occurred_at >= :start_at AND occurred_at < :end_at" . $companyFilter . "
             GROUP BY day ORDER BY day"
        );
        $statement->execute($parameters);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function revenueByPaymentMethod(
        string $startUtc,
        string $endUtc
    ): array {
        $parameters = [
            'start_at' => $startUtc, 'end_at' => $endUtc,
            'monthly_start_at' => $startUtc, 'monthly_end_at' => $endUtc,
        ];
        $transactionFilter = $this->companyFilter('t.company_id', $parameters, 'transaction_company_id');
        $monthlyFilter = $this->companyFilter('mp.company_id', $parameters, 'monthly_company_id');
        $query = 'SELECT revenue.payment_method, SUM(revenue.amount) AS total, COUNT(*) AS quantity
             FROM (
                SELECT CAST(t.payment_method AS CHAR) COLLATE utf8mb4_unicode_ci AS payment_method,
                       t.valor AS amount
                FROM transacoes t
                WHERE t.payment_date >= :start_at AND t.payment_date < :end_at
                  ' . $transactionFilter . '
                UNION ALL
                SELECT CAST(mp.payment_method AS CHAR) COLLATE utf8mb4_unicode_ci AS payment_method,
                       mp.amount
                FROM monthly_contract_payments mp
                WHERE mp.payment_date >= :monthly_start_at AND mp.payment_date < :monthly_end_at
                  ' . $monthlyFilter . '
             ) revenue
             GROUP BY revenue.payment_method
             ORDER BY total DESC';

        $statement = $this->connection->prepare($query);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function topParkingOperators(
        string $startLocal,
        string $endLocal,
        int $limit = 5
    ): array {
        $limit = max(1, min($limit, 20));
        $parameters = [
            'start_at' => $startLocal,
            'end_at' => $endLocal,
        ];
        $companyFilter = $this->companyFilter('vp.company_id', $parameters);
        $query = 'SELECT
                u.id_usuario AS operator_id,
                u.nome AS operator_name,
                u.email AS operator_email,
                COUNT(vp.id_vaga_preenchida) AS parked_count,
                COALESCE(SUM(t.valor), 0) AS generated_revenue
             FROM vagas_preenchidas vp
             LEFT JOIN usuario u
                ON u.id_usuario = vp.created_by
             LEFT JOIN transacoes t
                ON t.id_vaga_preenchida = vp.id_vaga_preenchida
             WHERE vp.hora_entrada >= :start_at
               AND vp.hora_entrada < :end_at
             ' . $companyFilter . '
             GROUP BY u.id_usuario, u.nome, u.email
             ORDER BY parked_count DESC, generated_revenue DESC
             LIMIT ' . $limit;
        $statement = $this->connection->prepare($query);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function occupancyRate(
        string $startLocal,
        string $endLocal
    ): float {
        $parameters = [
            'start_a' => $startLocal,
            'end_a' => $endLocal,
            'end_b' => $endLocal,
            'start_b' => $startLocal,
            'end_c' => $endLocal,
            'end_d' => $endLocal,
            'start_c' => $startLocal,
        ];
        $capacityFilter = $this->scope->isGlobal()
            ? ''
            : ' WHERE vd.company_id = :capacity_company_id';
        if (!$this->scope->isGlobal()) {
            $parameters['capacity_company_id'] = $this->scope->companyId();
        }
        $query = 'SELECT
                COALESCE(SUM(
                    GREATEST(
                        0,
                        TIMESTAMPDIFF(
                            SECOND,
                            GREATEST(vp.hora_entrada, :start_a),
                            LEAST(COALESCE(vp.hora_saida, :end_a), :end_b)
                        )
                    )
                ), 0) AS occupied_seconds,
                (SELECT COUNT(*) FROM vagas_disponiveis vd' . $capacityFilter . ')
                    * TIMESTAMPDIFF(SECOND, :start_b, :end_c)
                    AS capacity_seconds
             FROM vagas_preenchidas vp
             WHERE vp.hora_entrada < :end_d
               AND (vp.hora_saida IS NULL OR vp.hora_saida >= :start_c)';
        $query .= $this->companyFilter('vp.company_id', $parameters);

        $statement = $this->connection->prepare($query);
        $statement->execute($parameters);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        $capacity = (float) ($row['capacity_seconds'] ?? 0);

        return $capacity > 0
            ? ((float) $row['occupied_seconds'] / $capacity) * 100
            : 0.0;
    }

    public function recentTransactions(int $limit = 20): array
    {
        $limit = max(1, min($limit, 50));
        $parameters = [];
        $companyFilter = $this->companyFilter('t.company_id', $parameters);

        $query = 'SELECT
                t.id_transacao, t.valor, t.payment_method, t.payment_date,
                vp.placa, t.company_id, c.name AS company_name
             FROM transacoes t
             INNER JOIN vagas_preenchidas vp
                ON vp.id_vaga_preenchida = t.id_vaga_preenchida
             INNER JOIN companies c ON c.id = t.company_id
             WHERE 1 = 1 ' . $companyFilter . '
             ORDER BY t.payment_date DESC
             LIMIT ' . $limit;

        $statement = $this->connection->prepare($query);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function companyFilter(
        string $column,
        array &$parameters,
        string $parameter = 'company_id'
    ): string {
        if ($this->scope->isGlobal()) {
            return '';
        }

        $parameters[$parameter] = $this->scope->companyId();

        return ' AND ' . $column . ' = :' . $parameter;
    }

    private function hasTenantAwareAdjustments(): bool
    {
        static $exists = null;

        if ($exists !== null) {
            return $exists;
        }

        $statement = $this->prepareSystemStatement(
            'SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name'
        );
        $statement->execute([
            'table_name' => 'financial_adjustments',
            'column_name' => 'company_id',
        ]);

        $exists = (int) $statement->fetchColumn() > 0;

        return $exists;
    }
}
