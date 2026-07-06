<?php

declare(strict_types=1);

namespace App\Finance\Repositories;

use PDO;

final class FinancialReportRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function summary(string $startUtc, string $endUtc): array
    {
        $statement = $this->connection->prepare(
            'SELECT
                COALESCE(SUM(t.valor), 0) AS gross_revenue,
                COALESCE(AVG(t.valor), 0) AS average_ticket,
                COUNT(*) AS transaction_count,
                COALESCE((
                    SELECT SUM(fa.amount)
                    FROM financial_adjustments fa
                    WHERE fa.created_at >= :adjustment_start
                      AND fa.created_at < :adjustment_end
                ), 0) AS adjustments
             FROM transacoes t
             WHERE t.payment_date >= :start_at
               AND t.payment_date < :end_at'
        );
        $statement->execute([
            'start_at' => $startUtc,
            'end_at' => $endUtc,
            'adjustment_start' => $startUtc,
            'adjustment_end' => $endUtc,
        ]);

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function revenueByDay(string $startUtc, string $endUtc): array
    {
        $statement = $this->connection->prepare(
            "SELECT DATE(payment_date) AS day, SUM(valor) AS total
             FROM transacoes
             WHERE payment_date >= :start_at AND payment_date < :end_at
             GROUP BY DATE(payment_date)
             ORDER BY day"
        );
        $statement->execute(['start_at' => $startUtc, 'end_at' => $endUtc]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function revenueByPaymentMethod(
        string $startUtc,
        string $endUtc
    ): array {
        $statement = $this->connection->prepare(
            'SELECT payment_method, SUM(valor) AS total, COUNT(*) AS quantity
             FROM transacoes
             WHERE payment_date >= :start_at AND payment_date < :end_at
             GROUP BY payment_method
             ORDER BY total DESC'
        );
        $statement->execute(['start_at' => $startUtc, 'end_at' => $endUtc]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function topParkingOperators(
        string $startLocal,
        string $endLocal,
        int $limit = 5
    ): array {
        $limit = max(1, min($limit, 20));
        $statement = $this->connection->prepare(
            'SELECT
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
             LIMIT ' . $limit
        );
        $statement->execute([
            'start_at' => $startLocal,
            'end_at' => $endLocal,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function occupancyRate(
        string $startLocal,
        string $endLocal
    ): float {
        $statement = $this->connection->prepare(
            'SELECT
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
                (SELECT COUNT(*) FROM vagas_disponiveis)
                    * TIMESTAMPDIFF(SECOND, :start_b, :end_c)
                    AS capacity_seconds
             FROM vagas_preenchidas vp
             WHERE vp.hora_entrada < :end_d
               AND (vp.hora_saida IS NULL OR vp.hora_saida >= :start_c)'
        );
        $statement->execute([
            'start_a' => $startLocal,
            'end_a' => $endLocal,
            'end_b' => $endLocal,
            'start_b' => $startLocal,
            'end_c' => $endLocal,
            'end_d' => $endLocal,
            'start_c' => $startLocal,
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        $capacity = (float) ($row['capacity_seconds'] ?? 0);

        return $capacity > 0
            ? ((float) $row['occupied_seconds'] / $capacity) * 100
            : 0.0;
    }

    public function recentTransactions(int $limit = 20): array
    {
        $limit = max(1, min($limit, 50));

        return $this->connection->query(
            'SELECT
                t.id_transacao, t.valor, t.payment_method, t.payment_date,
                vp.placa
             FROM transacoes t
             INNER JOIN vagas_preenchidas vp
                ON vp.id_vaga_preenchida = t.id_vaga_preenchida
             ORDER BY t.payment_date DESC
             LIMIT ' . $limit
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}
