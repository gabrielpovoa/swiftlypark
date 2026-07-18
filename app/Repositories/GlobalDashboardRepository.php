<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class GlobalDashboardRepository extends BaseRepository
{
    public function overview(): array
    {
        return $this->withoutTenantFilter(function (): array {
            $statement = $this->prepareGlobalStatement(
                'SELECT
                    (SELECT COUNT(*) FROM companies WHERE deleted_at IS NULL) AS active_companies,
                    (SELECT COUNT(*) FROM usuario WHERE deleted_at IS NULL) AS registered_users,
                    (SELECT COUNT(*)
                        FROM vagas_preenchidas vp
                        INNER JOIN companies c ON c.id = vp.company_id AND c.deleted_at IS NULL
                        WHERE vp.hora_saida IS NULL
                    ) AS active_sessions,
                    (SELECT COUNT(*)
                        FROM vagas_preenchidas vp
                        INNER JOIN companies c ON c.id = vp.company_id AND c.deleted_at IS NULL
                        WHERE vp.hora_entrada IS NOT NULL
                    ) AS completed_checkins,
                    ((SELECT COALESCE(SUM(t.valor), 0)
                        FROM transacoes t
                        INNER JOIN companies c ON c.id = t.company_id AND c.deleted_at IS NULL
                    ) + (SELECT COALESCE(SUM(mp.amount), 0)
                        FROM monthly_contract_payments mp
                        INNER JOIN companies c ON c.id = mp.company_id AND c.deleted_at IS NULL
                    )) AS total_revenue,
                    (SELECT COUNT(*)
                        FROM monthly_contracts mc
                        INNER JOIN companies c ON c.id = mc.company_id AND c.deleted_at IS NULL
                        WHERE mc.status = \'ACTIVE\'
                          AND mc.starts_at <= CURRENT_DATE()
                          AND mc.expires_at >= CURRENT_DATE()
                    ) AS active_monthly_contracts,
                    (SELECT COALESCE(SUM(mc.monthly_amount), 0)
                        FROM monthly_contracts mc
                        INNER JOIN companies c ON c.id = mc.company_id AND c.deleted_at IS NULL
                        WHERE mc.status = \'ACTIVE\'
                          AND mc.starts_at <= CURRENT_DATE()
                          AND mc.expires_at >= CURRENT_DATE()
                    ) AS monthly_recurring_revenue,
                    (SELECT COALESCE(SUM(mp.amount), 0)
                        FROM monthly_contract_payments mp
                        INNER JOIN companies c ON c.id = mp.company_id AND c.deleted_at IS NULL
                        WHERE mp.payment_date >= DATE_FORMAT(CURRENT_DATE(), \'%Y-%m-01\')
                          AND mp.payment_date < DATE_FORMAT(CURRENT_DATE() + INTERVAL 1 MONTH, \'%Y-%m-01\')
                    ) AS monthly_revenue_received,
                    (SELECT COUNT(*) FROM audit_logs
                        WHERE action IN (
                            \'CROSS_TENANT_ACCESS_ATTEMPT\',
                            \'SYSTEMATIC_TENANT_SCAN_DETECTED\',
                            \'UNAUTHORIZED_ACCESS_ATTEMPT\'
                        )
                        AND created_at >= UTC_TIMESTAMP(6) - INTERVAL 30 DAY
                    ) AS active_investigations,
                    (SELECT COUNT(*) FROM companies
                        WHERE deleted_at IS NULL
                          AND created_at >= UTC_TIMESTAMP(6) - INTERVAL 30 DAY
                    ) AS monthly_growth'
            );
            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];

            return [
                'active_companies' => (int) ($row['active_companies'] ?? 0),
                'registered_users' => (int) ($row['registered_users'] ?? 0),
                'active_sessions' => (int) ($row['active_sessions'] ?? 0),
                'completed_checkins' => (int) ($row['completed_checkins'] ?? 0),
                'total_revenue' => (float) ($row['total_revenue'] ?? 0),
                'active_monthly_contracts' => (int) ($row['active_monthly_contracts'] ?? 0),
                'monthly_recurring_revenue' => (float) ($row['monthly_recurring_revenue'] ?? 0),
                'monthly_revenue_received' => (float) ($row['monthly_revenue_received'] ?? 0),
                'active_investigations' => (int) ($row['active_investigations'] ?? 0),
                'monthly_growth' => (int) ($row['monthly_growth'] ?? 0),
            ];
        });
    }

    public function topCompaniesByUsage(int $limit = 5): array
    {
        $limit = max(1, min($limit, 10));

        return $this->withoutTenantFilter(function () use ($limit): array {
            $statement = $this->prepareGlobalStatement(
                'SELECT
                    c.id,
                    c.name,
                    COALESCE(usage_totals.checkins, 0) AS checkins,
                    COALESCE(revenue_totals.revenue, 0) AS revenue
                 FROM companies c
                 LEFT JOIN (
                    SELECT company_id, COUNT(*) AS checkins
                    FROM vagas_preenchidas
                    GROUP BY company_id
                 ) usage_totals ON usage_totals.company_id = c.id
                 LEFT JOIN (
                    SELECT revenue.company_id, SUM(revenue.amount) AS revenue
                    FROM (
                        SELECT company_id, valor AS amount FROM transacoes
                        UNION ALL
                        SELECT company_id, amount FROM monthly_contract_payments
                    ) revenue
                    GROUP BY revenue.company_id
                 ) revenue_totals ON revenue_totals.company_id = c.id
                 WHERE c.deleted_at IS NULL
                 GROUP BY c.id, c.name, usage_totals.checkins, revenue_totals.revenue
                 ORDER BY checkins DESC, revenue DESC, c.name ASC
                 LIMIT ' . $limit
            );
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function revenueByCompany(int $limit = 5): array
    {
        $limit = max(1, min($limit, 10));

        return $this->withoutTenantFilter(function () use ($limit): array {
            $statement = $this->prepareGlobalStatement(
                'SELECT
                    c.id,
                    c.name,
                    COALESCE(MAX(revenue_totals.revenue), 0) AS revenue
                 FROM companies c
                 LEFT JOIN (
                    SELECT revenue.company_id, SUM(revenue.amount) AS revenue
                    FROM (
                        SELECT company_id, valor AS amount FROM transacoes
                        UNION ALL
                        SELECT company_id, amount FROM monthly_contract_payments
                    ) revenue
                    GROUP BY revenue.company_id
                 ) revenue_totals ON revenue_totals.company_id = c.id
                 WHERE c.deleted_at IS NULL
                 GROUP BY c.id, c.name
                 ORDER BY revenue DESC, c.name ASC
                 LIMIT ' . $limit
            );
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function recentSecurityAlerts(int $limit = 5): array
    {
        $limit = max(1, min($limit, 10));

        return $this->withoutTenantFilter(function () use ($limit): array {
            $statement = $this->prepareGlobalStatement(
                'SELECT
                    al.id,
                    al.company_id,
                    c.name AS company_name,
                    al.actor_email,
                    al.action,
                    al.entity,
                    al.entity_id,
                    al.new_values,
                    al.ip_address,
                    al.created_at
                 FROM audit_logs al
                 LEFT JOIN companies c ON c.id = al.company_id
                 WHERE al.action IN (
                    \'CROSS_TENANT_ACCESS_ATTEMPT\',
                    \'SYSTEMATIC_TENANT_SCAN_DETECTED\',
                    \'UNAUTHORIZED_ACCESS_ATTEMPT\'
                 )
                 ORDER BY al.created_at DESC
                 LIMIT ' . $limit
            );
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC);
        });
    }
}
