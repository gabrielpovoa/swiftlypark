<?php

declare(strict_types=1);

namespace App\Finance\Repositories;

use PDO;

final class FinancialReportRepository extends \App\Repositories\BaseRepository
{

    public function summary(string $startUtc, string $endUtc): array
    {
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

        $query = 'SELECT
                COALESCE(SUM(t.valor), 0) AS gross_revenue,
                COALESCE(AVG(t.valor), 0) AS average_ticket,
                COUNT(*) AS transaction_count,
                COALESCE(' . $adjustmentsExpression . ', 0) AS adjustments
             FROM transacoes t
             WHERE t.payment_date >= :start_at
               AND t.payment_date < :end_at';
        $this->applyTenantFilter($query, $parameters, 't.company_id');

        $statement = $this->connection->prepare($query);
        $statement->execute($parameters);

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function revenueByDay(string $startUtc, string $endUtc): array
    {
        $parameters = ['start_at' => $startUtc, 'end_at' => $endUtc];
        $query = "SELECT DATE(payment_date) AS day, SUM(valor) AS total
             FROM transacoes
             WHERE payment_date >= :start_at AND payment_date < :end_at
             GROUP BY DATE(payment_date)
             ORDER BY day";
        $this->applyTenantFilter($query, $parameters, 'company_id');

        $statement = $this->connection->prepare($query);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function revenueByPaymentMethod(
        string $startUtc,
        string $endUtc
    ): array {
        $parameters = ['start_at' => $startUtc, 'end_at' => $endUtc];
        $query = 'SELECT payment_method, SUM(valor) AS total, COUNT(*) AS quantity
             FROM transacoes
             WHERE payment_date >= :start_at AND payment_date < :end_at
             GROUP BY payment_method
             ORDER BY total DESC';
        $this->applyTenantFilter($query, $parameters, 'company_id');

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

        $statement = $this->connection->prepare($query);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function hasTenantAwareAdjustments(): bool
    {
        static $exists = null;

        if ($exists !== null) {
            return $exists;
        }

        $statement = $this->connection->prepare(
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
