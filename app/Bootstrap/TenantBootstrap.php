<?php

declare(strict_types=1);

namespace App\Bootstrap;

use Config\Database;
use PDO;

final class TenantBootstrap
{
    public function __construct(private ?PDO $connection = null)
    {
    }

    public function boot(): void
    {
        $connection = $this->connection ?? (new Database())->connect();

        $this->ensureTenantSchema($connection);
        $this->ensureIdentityGovernanceSchema($connection);
        $this->ensureOperationalTenantSchema($connection);
        $this->ensureFinanceTenantSchema($connection);
        $this->ensureAuditSchema($connection);
        $companyId = $this->ensureSwiftlyParkCompany($connection);
        $this->backfillOperationalTenantData($connection, $companyId);
        $this->backfillFinanceTenantData($connection, $companyId);
        $this->ensureLegacyMemberships($connection, $companyId);
    }

    private function ensureTenantSchema(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE IF NOT EXISTS companies (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(120) NOT NULL,
                logo_path VARCHAR(255) NULL,
                deleted_at DATETIME(6) NULL,
                created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                    ON UPDATE CURRENT_TIMESTAMP(6),
                PRIMARY KEY (id),
                UNIQUE KEY uq_companies_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        $connection->exec(
            "CREATE TABLE IF NOT EXISTS company_user (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id BIGINT UNSIGNED NOT NULL,
                user_id INT NOT NULL,
                role_id BIGINT UNSIGNED NULL,
                created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                PRIMARY KEY (id),
                UNIQUE KEY uq_company_user_company_user (company_id, user_id),
                CONSTRAINT fk_company_user_company FOREIGN KEY (company_id)
                    REFERENCES companies (id) ON UPDATE RESTRICT ON DELETE CASCADE,
                CONSTRAINT fk_company_user_user FOREIGN KEY (user_id)
                    REFERENCES usuario (id_usuario) ON UPDATE RESTRICT ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        if (!$this->columnExists($connection, 'companies', 'deleted_at')) {
            $connection->exec(
                'ALTER TABLE companies
                    ADD COLUMN deleted_at DATETIME(6) NULL AFTER slug'
            );
        }

        if (!$this->columnExists($connection, 'companies', 'logo_path')) {
            $connection->exec(
                'ALTER TABLE companies
                    ADD COLUMN logo_path VARCHAR(255) NULL AFTER slug'
            );
        }

        if (!$this->indexExists($connection, 'companies', 'idx_companies_deleted_at')) {
            $connection->exec(
                'ALTER TABLE companies
                    ADD INDEX idx_companies_deleted_at (deleted_at)'
            );
        }
    }

    private function ensureIdentityGovernanceSchema(PDO $connection): void
    {
        if (!$this->tableExists($connection, 'usuario')) {
            return;
        }

        if (!$this->columnExists($connection, 'usuario', 'deleted_at')) {
            $connection->exec(
                'ALTER TABLE usuario
                    ADD COLUMN deleted_at DATETIME(6) NULL AFTER senha_hash'
            );
        }

        if (!$this->indexExists($connection, 'usuario', 'idx_usuario_deleted_at')) {
            $connection->exec(
                'ALTER TABLE usuario
                    ADD INDEX idx_usuario_deleted_at (deleted_at)'
            );
        }

        if (!$this->columnExists($connection, 'usuario', 'password_reset_required')) {
            $connection->exec(
                'ALTER TABLE usuario
                    ADD COLUMN password_reset_required TINYINT(1) NOT NULL DEFAULT 0 AFTER deleted_at'
            );
        }
    }

    private function ensureSwiftlyParkCompany(PDO $connection): int
    {
        $select = $connection->prepare(
            'SELECT id FROM companies WHERE slug = :slug LIMIT 1'
        );
        $select->execute(['slug' => 'swiftlypark']);
        $companyId = $select->fetchColumn();

        if ($companyId !== false) {
            return (int) $companyId;
        }

        $existingCompanyId = $connection
            ->query('SELECT id FROM companies ORDER BY id ASC LIMIT 1')
            ->fetchColumn();

        if ($existingCompanyId !== false) {
            $update = $connection->prepare(
                'UPDATE companies
                 SET name = :name, slug = :slug, updated_at = NOW()
                 WHERE id = :id'
            );
            $update->execute([
                'name' => 'SwiftlyPark',
                'slug' => 'swiftlypark',
                'id' => (int) $existingCompanyId,
            ]);

            return (int) $existingCompanyId;
        }

        $insert = $connection->prepare(
            'INSERT INTO companies (name, slug, created_at, updated_at)
             VALUES (:name, :slug, NOW(), NOW())'
        );
        $insert->execute([
            'name' => 'SwiftlyPark',
            'slug' => 'swiftlypark',
        ]);

        return (int) $connection->lastInsertId();
    }

    private function ensureOperationalTenantSchema(PDO $connection): void
    {
        foreach (['vagas_disponiveis', 'vagas_preenchidas', 'transacoes'] as $table) {
            if (!$this->tableExists($connection, $table)) {
                continue;
            }

            if (!$this->columnExists($connection, $table, 'company_id')) {
                $connection->exec(
                    sprintf(
                        'ALTER TABLE %s ADD COLUMN company_id BIGINT UNSIGNED NULL',
                        $table
                    )
                );
            }

            $indexName = 'idx_' . $table . '_company_id';
            if (!$this->indexExists($connection, $table, $indexName)) {
                $connection->exec(
                    sprintf(
                        'ALTER TABLE %s ADD INDEX %s (company_id)',
                        $table,
                        $indexName
                    )
                );
            }
        }
    }

    private function ensureAuditSchema(PDO $connection): void
    {
        if (!$this->tableExists($connection, 'audit_logs')) {
            return;
        }

        $connection->exec(
            'ALTER TABLE audit_logs
                MODIFY COLUMN action VARCHAR(64) NOT NULL'
        );

        if (!$this->columnExists($connection, 'audit_logs', 'company_id')) {
            $connection->exec(
                'ALTER TABLE audit_logs
                    ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER user_id'
            );
        }

        if (!$this->indexExists($connection, 'audit_logs', 'idx_audit_logs_company_created')) {
            $connection->exec(
                'ALTER TABLE audit_logs
                    ADD INDEX idx_audit_logs_company_created (company_id, created_at)'
            );
        }
    }

    private function ensureFinanceTenantSchema(PDO $connection): void
    {
        if (!$this->tableExists($connection, 'financial_adjustments')) {
            return;
        }

        if (!$this->columnExists($connection, 'financial_adjustments', 'company_id')) {
            $connection->exec(
                'ALTER TABLE financial_adjustments
                    ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER id'
            );
        }

        if (!$this->indexExists(
            $connection,
            'financial_adjustments',
            'idx_financial_adjustments_company_created'
        )) {
            $connection->exec(
                'ALTER TABLE financial_adjustments
                    ADD INDEX idx_financial_adjustments_company_created (company_id, created_at)'
            );
        }
    }

    private function ensureLegacyMemberships(PDO $connection, int $companyId): void
    {
        $activeCondition = $this->columnExists($connection, 'usuario', 'deleted_at')
            ? ' AND u.deleted_at IS NULL'
            : '';
        $insert = $connection->prepare(
            'INSERT INTO company_user (company_id, user_id, role_id, created_at)
             SELECT :company_id_select, u.id_usuario, NULL, NOW()
             FROM usuario u
             LEFT JOIN company_user cu
                ON cu.company_id = :company_id_join
               AND cu.user_id = u.id_usuario
             WHERE cu.id IS NULL' . $activeCondition
        );
        $insert->execute([
            'company_id_select' => $companyId,
            'company_id_join' => $companyId,
        ]);
    }

    private function backfillOperationalTenantData(PDO $connection, int $companyId): void
    {
        foreach (['vagas_disponiveis', 'vagas_preenchidas', 'transacoes'] as $table) {
            if (!$this->tableExists($connection, $table)
                || !$this->columnExists($connection, $table, 'company_id')) {
                continue;
            }

            $statement = $connection->prepare(
                sprintf(
                    'UPDATE %s SET company_id = :company_id WHERE company_id IS NULL',
                    $table
                )
            );
            $statement->execute(['company_id' => $companyId]);

            $connection->exec(
                sprintf(
                    'ALTER TABLE %s MODIFY COLUMN company_id BIGINT UNSIGNED NOT NULL',
                    $table
                )
            );

            $constraintName = 'fk_' . $table . '_company';
            if (!$this->constraintExists($connection, $table, $constraintName)) {
                $connection->exec(
                    sprintf(
                        'ALTER TABLE %s
                            ADD CONSTRAINT %s
                            FOREIGN KEY (company_id) REFERENCES companies (id)
                            ON UPDATE RESTRICT ON DELETE RESTRICT',
                        $table,
                        $constraintName
                    )
                );
            }
        }
    }

    private function backfillFinanceTenantData(PDO $connection, int $companyId): void
    {
        if (!$this->tableExists($connection, 'financial_adjustments')
            || !$this->columnExists($connection, 'financial_adjustments', 'company_id')) {
            return;
        }

        $connection->exec(
            'UPDATE financial_adjustments fa
             INNER JOIN transacoes t ON t.id_transacao = fa.transaction_id
             SET fa.company_id = t.company_id
             WHERE fa.company_id IS NULL
               AND t.company_id IS NOT NULL'
        );

        $statement = $connection->prepare(
            'UPDATE financial_adjustments
             SET company_id = :company_id
             WHERE company_id IS NULL'
        );
        $statement->execute(['company_id' => $companyId]);

        $connection->exec(
            'ALTER TABLE financial_adjustments
                MODIFY COLUMN company_id BIGINT UNSIGNED NOT NULL'
        );

        if (!$this->constraintExists(
            $connection,
            'financial_adjustments',
            'fk_financial_adjustments_company'
        )) {
            $connection->exec(
                'ALTER TABLE financial_adjustments
                    ADD CONSTRAINT fk_financial_adjustments_company
                    FOREIGN KEY (company_id) REFERENCES companies (id)
                    ON UPDATE RESTRICT ON DELETE RESTRICT'
            );
        }
    }

    private function columnExists(PDO $connection, string $table, string $column): bool
    {
        $statement = $connection->prepare(
            'SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name'
        );
        $statement->execute([
            'table_name' => $table,
            'column_name' => $column,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    private function tableExists(PDO $connection, string $table): bool
    {
        $statement = $connection->prepare(
            'SELECT COUNT(*)
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name'
        );
        $statement->execute(['table_name' => $table]);

        return (int) $statement->fetchColumn() > 0;
    }

    private function indexExists(PDO $connection, string $table, string $index): bool
    {
        $statement = $connection->prepare(
            'SELECT COUNT(*)
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND INDEX_NAME = :index_name'
        );
        $statement->execute([
            'table_name' => $table,
            'index_name' => $index,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    private function constraintExists(PDO $connection, string $table, string $constraint): bool
    {
        $statement = $connection->prepare(
            'SELECT COUNT(*)
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND CONSTRAINT_NAME = :constraint_name'
        );
        $statement->execute([
            'table_name' => $table,
            'constraint_name' => $constraint,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }
}
