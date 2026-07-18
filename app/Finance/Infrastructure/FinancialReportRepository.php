<?php

declare(strict_types=1);

namespace App\Finance\Infrastructure;

use PDO;

final class FinancialReportRepository extends \App\Repositories\BaseRepository
{

    public function summary(string $startUtc, string $endUtc): array
    {
        $companyId = $this->assertTenantContext();
        $adjustmentsExpression = '0';
        $parameters = [
            'start_at' => $startUtc,
            'end_at' => $endUtc,
        ];

        if ($this->hasTenantAwareAdjustments()) {
            $adjustmentsExpression = '(
                    SELECT SUM(fa.amount)
                    FROM financial_adjustments fa
                    WHERE fa.created_at >= :adjustment_start
                      AND fa.created_at < :adjustment_end
                      AND fa.company_id = :adjustment_company_id
                )';
            $parameters['adjustment_start'] = $startUtc;
            $parameters['adjustment_end'] = $endUtc;
            $parameters['adjustment_company_id'] = $this->assertTenantContext();
        }

        $parameters['company_id'] = $companyId;
        $parameters['monthly_company_id'] = $companyId;
        $parameters['monthly_start_at'] = $startUtc;
        $parameters['monthly_end_at'] = $endUtc;
        $query = 'SELECT
                COALESCE(SUM(revenue.amount), 0) AS gross_revenue,
                COALESCE(SUM(CASE WHEN revenue.origin = "ROTATING" THEN revenue.amount ELSE 0 END), 0) AS rotating_revenue,
                COALESCE(SUM(CASE WHEN revenue.origin = "MONTHLY" THEN revenue.amount ELSE 0 END), 0) AS monthly_revenue,
                COALESCE(AVG(revenue.amount), 0) AS average_ticket,
                SUM(CASE WHEN revenue.origin = "ROTATING" THEN 1 ELSE 0 END) AS rotating_transaction_count,
                SUM(CASE WHEN revenue.origin = "MONTHLY" THEN 1 ELSE 0 END) AS monthly_payment_count,
                COUNT(*) AS transaction_count,
                COALESCE(' . $adjustmentsExpression . ', 0) AS adjustments
             FROM (
                SELECT t.valor AS amount, "ROTATING" AS origin
                FROM transacoes t
                WHERE t.payment_date >= :start_at
                  AND t.payment_date < :end_at
                  AND t.company_id = :company_id
                UNION ALL
                SELECT mp.amount, "MONTHLY" AS origin
                FROM monthly_contract_payments mp
                WHERE mp.payment_date >= :monthly_start_at
                  AND mp.payment_date < :monthly_end_at
                  AND mp.company_id = :monthly_company_id
             ) revenue';

        $statement = $this->prepareTenantStatement($query, $parameters);
        $statement->execute($parameters);

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function revenueByDay(string $startUtc, string $endUtc): array
    {
        $companyId = $this->assertTenantContext();
        $parameters = [
            'start_at' => $startUtc, 'end_at' => $endUtc, 'company_id' => $companyId,
            'monthly_start_at' => $startUtc, 'monthly_end_at' => $endUtc,
            'monthly_company_id' => $companyId,
        ];
        $query = "SELECT revenue.day,
                    SUM(CASE WHEN revenue.origin = 'ROTATING' THEN revenue.amount ELSE 0 END) AS rotating_total,
                    SUM(CASE WHEN revenue.origin = 'MONTHLY' THEN revenue.amount ELSE 0 END) AS monthly_total,
                    SUM(revenue.amount) AS total
             FROM (
                SELECT DATE(CONVERT_TZ(t.payment_date, '+00:00', '-03:00')) AS day,
                       t.valor AS amount, 'ROTATING' AS origin
                FROM transacoes t
                WHERE t.payment_date >= :start_at AND t.payment_date < :end_at
                  AND t.company_id = :company_id
                UNION ALL
                SELECT DATE(CONVERT_TZ(mp.payment_date, '+00:00', '-03:00')) AS day,
                       mp.amount, 'MONTHLY' AS origin
                FROM monthly_contract_payments mp
                WHERE mp.payment_date >= :monthly_start_at AND mp.payment_date < :monthly_end_at
                  AND mp.company_id = :monthly_company_id
             ) revenue
             GROUP BY revenue.day
             ORDER BY day";

        $statement = $this->prepareTenantStatement($query, $parameters);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function revenueByPaymentMethod(
        string $startUtc,
        string $endUtc
    ): array {
        $companyId = $this->assertTenantContext();
        $parameters = [
            'start_at' => $startUtc, 'end_at' => $endUtc, 'company_id' => $companyId,
            'monthly_start_at' => $startUtc, 'monthly_end_at' => $endUtc,
            'monthly_company_id' => $companyId,
        ];
        $query = 'SELECT revenue.payment_method, SUM(revenue.amount) AS total, COUNT(*) AS quantity
             FROM (
                SELECT CAST(t.payment_method AS CHAR) COLLATE utf8mb4_unicode_ci AS payment_method,
                       t.valor AS amount
                FROM transacoes t
                WHERE t.payment_date >= :start_at AND t.payment_date < :end_at
                  AND t.company_id = :company_id
                UNION ALL
                SELECT CAST(mp.payment_method AS CHAR) COLLATE utf8mb4_unicode_ci AS payment_method,
                       mp.amount
                FROM monthly_contract_payments mp
                WHERE mp.payment_date >= :monthly_start_at AND mp.payment_date < :monthly_end_at
                  AND mp.company_id = :monthly_company_id
             ) revenue
             GROUP BY revenue.payment_method
             ORDER BY total DESC';

        $statement = $this->prepareTenantStatement($query, $parameters);
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
             GROUP BY u.id_usuario, u.nome, u.email
             ORDER BY parked_count DESC, generated_revenue DESC
             LIMIT ' . $limit;
        $this->applyTenantFilter($query, $parameters, 'vp.company_id');

        $statement = $this->prepareTenantStatement($query, $parameters);
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
            'capacity_company_id' => $this->assertTenantContext(),
        ];
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
                (SELECT COUNT(*) FROM vagas_disponiveis vd
                 WHERE vd.company_id = :capacity_company_id)
                    * TIMESTAMPDIFF(SECOND, :start_b, :end_c)
                    AS capacity_seconds
             FROM vagas_preenchidas vp
             WHERE vp.hora_entrada < :end_d
               AND (vp.hora_saida IS NULL OR vp.hora_saida >= :start_c)';
        $this->applyTenantFilter($query, $parameters, 'vp.company_id');

        $statement = $this->prepareTenantStatement($query, $parameters);
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

        $query = 'SELECT
                t.id_transacao, t.valor, t.payment_method, t.payment_date,
                vp.placa
             FROM transacoes t
             INNER JOIN vagas_preenchidas vp
                ON vp.id_vaga_preenchida = t.id_vaga_preenchida
             ORDER BY t.payment_date DESC
             LIMIT ' . $limit;
        $parameters = [];
        $this->applyTenantFilter($query, $parameters, 't.company_id');

        $statement = $this->prepareTenantStatement($query, $parameters);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
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

