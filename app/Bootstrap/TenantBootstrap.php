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
        $this->ensureOccupancyBillingModel($connection);
        $this->ensureFinanceTenantSchema($connection);
        $this->ensurePricingSchema($connection);
        $this->ensureMonthlyContractsSchema($connection);
        $this->ensureAuditSchema($connection);
        $companyId = $this->ensureSwiftlyParkCompany($connection);
        $this->backfillOperationalTenantData($connection, $companyId);
        $this->backfillFinanceTenantData($connection, $companyId);
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

        if (!$this->columnExists($connection, 'companies', 'is_mensalista')) {
            $connection->exec(
                'ALTER TABLE companies
                    ADD COLUMN is_mensalista TINYINT(1) NOT NULL DEFAULT 0 AFTER logo_path'
            );
        }

        if (!$this->columnExists($connection, 'companies', 'legal_name')) {
            $connection->exec(
                'ALTER TABLE companies ADD COLUMN legal_name VARCHAR(255) NULL AFTER name'
            );
        }

        if (!$this->columnExists($connection, 'companies', 'trade_name')) {
            $connection->exec(
                'ALTER TABLE companies ADD COLUMN trade_name VARCHAR(255) NULL AFTER legal_name'
            );
            $connection->exec(
                "UPDATE companies SET trade_name = name WHERE trade_name IS NULL OR trade_name = ''"
            );
        }

        if (!$this->columnExists($connection, 'companies', 'cnpj')) {
            $connection->exec(
                'ALTER TABLE companies ADD COLUMN cnpj VARCHAR(18) NULL AFTER trade_name'
            );
        }
    }

    private function ensurePricingSchema(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE IF NOT EXISTS tarifarios (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id BIGINT UNSIGNED NOT NULL,
                tipo_veiculo VARCHAR(30) NOT NULL,
                valor_base DECIMAL(10, 2) NOT NULL,
                valor_adicional DECIMAL(10, 2) NOT NULL,
                tolerancia_minutos INT UNSIGNED NOT NULL DEFAULT 0,
                frequencia_adicional INT UNSIGNED NOT NULL,
                created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                    ON UPDATE CURRENT_TIMESTAMP(6),
                PRIMARY KEY (id),
                UNIQUE KEY uq_tarifarios_company_vehicle (company_id, tipo_veiculo),
                KEY idx_tarifarios_company (company_id),
                CONSTRAINT fk_tarifarios_company FOREIGN KEY (company_id)
                    REFERENCES companies (id) ON UPDATE RESTRICT ON DELETE CASCADE,
                CONSTRAINT chk_tarifarios_tipo_veiculo CHECK (
                    tipo_veiculo IN ('carro', 'moto', 'caminhao', 'app')
                ),
                CONSTRAINT chk_tarifarios_valor_base CHECK (valor_base >= 0),
                CONSTRAINT chk_tarifarios_valor_adicional CHECK (valor_adicional >= 0),
                CONSTRAINT chk_tarifarios_frequencia CHECK (frequencia_adicional > 0)
            ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    private function ensureMonthlyContractsSchema(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE IF NOT EXISTS monthly_contracts (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id BIGINT UNSIGNED NOT NULL,
                customer_name VARCHAR(120) NOT NULL,
                vehicle_plate VARCHAR(10) NOT NULL,
                vehicle_type VARCHAR(30) NOT NULL,
                monthly_amount DECIMAL(10, 2) NOT NULL,
                starts_at DATE NOT NULL,
                expires_at DATE NOT NULL,
                status ENUM('ACTIVE', 'CANCELLED') NOT NULL DEFAULT 'ACTIVE',
                created_by INT NOT NULL,
                updated_by INT NOT NULL,
                created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
                PRIMARY KEY (id),
                UNIQUE KEY uq_monthly_contract_company_plate (company_id, vehicle_plate),
                KEY idx_monthly_contract_validity (company_id, status, starts_at, expires_at),
                CONSTRAINT fk_monthly_contract_company FOREIGN KEY (company_id) REFERENCES companies (id) ON UPDATE RESTRICT ON DELETE CASCADE,
                CONSTRAINT fk_monthly_contract_created_by FOREIGN KEY (created_by) REFERENCES usuario (id_usuario) ON UPDATE RESTRICT ON DELETE RESTRICT,
                CONSTRAINT fk_monthly_contract_updated_by FOREIGN KEY (updated_by) REFERENCES usuario (id_usuario) ON UPDATE RESTRICT ON DELETE RESTRICT,
                CONSTRAINT chk_monthly_contract_vehicle_type CHECK (vehicle_type IN ('carro', 'moto', 'caminhao', 'app')),
                CONSTRAINT chk_monthly_contract_amount CHECK (monthly_amount > 0),
                CONSTRAINT chk_monthly_contract_dates CHECK (expires_at >= starts_at)
            ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        $connection->exec(
            "CREATE TABLE IF NOT EXISTS monthly_contract_payments (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id BIGINT UNSIGNED NOT NULL,
                contract_id BIGINT UNSIGNED NOT NULL,
                amount DECIMAL(10, 2) NOT NULL,
                payment_method ENUM('PIX', 'CARD', 'CASH') NOT NULL,
                period_start DATE NOT NULL,
                period_end DATE NOT NULL,
                payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_by INT NOT NULL,
                created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                PRIMARY KEY (id),
                KEY idx_monthly_payment_company_date (company_id, payment_date),
                KEY idx_monthly_payment_contract (contract_id, payment_date),
                CONSTRAINT fk_monthly_payment_company FOREIGN KEY (company_id) REFERENCES companies (id) ON UPDATE RESTRICT ON DELETE CASCADE,
                CONSTRAINT fk_monthly_payment_contract FOREIGN KEY (contract_id) REFERENCES monthly_contracts (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
                CONSTRAINT fk_monthly_payment_created_by FOREIGN KEY (created_by) REFERENCES usuario (id_usuario) ON UPDATE RESTRICT ON DELETE RESTRICT,
                CONSTRAINT chk_monthly_payment_amount CHECK (amount > 0),
                CONSTRAINT chk_monthly_payment_period CHECK (period_end >= period_start)
            ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    private function ensureOccupancyBillingModel(PDO $connection): void
    {
        if (!$this->tableExists($connection, 'vagas_preenchidas')) {
            return;
        }
        if (!$this->columnExists($connection, 'vagas_preenchidas', 'billing_model')) {
            $connection->exec(
                "ALTER TABLE vagas_preenchidas
                 ADD COLUMN billing_model ENUM('MONTHLY', 'ROTATING') NOT NULL DEFAULT 'ROTATING'
                 AFTER tipo_veiculo"
            );
        }
        if (!$this->indexExists($connection, 'vagas_preenchidas', 'idx_vagas_preenchidas_billing_model')) {
            $connection->exec(
                'ALTER TABLE vagas_preenchidas
                 ADD INDEX idx_vagas_preenchidas_billing_model (company_id, billing_model)'
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

        if ($this->tableExists($connection, 'companies')
            && $this->tableExists($connection, 'permissions')
            && $this->tableExists($connection, 'company_user')
            && !$this->tableExists($connection, 'company_user_permissions')) {
            $connection->exec(
                "CREATE TABLE company_user_permissions (
                    user_id INT NOT NULL,
                    company_id BIGINT UNSIGNED NOT NULL,
                    permission_id BIGINT UNSIGNED NOT NULL,
                    granted_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                    granted_by INT NOT NULL,
                    PRIMARY KEY (user_id, company_id, permission_id),
                    CONSTRAINT fk_company_user_permissions_user
                        FOREIGN KEY (user_id)
                        REFERENCES usuario (id_usuario)
                        ON UPDATE RESTRICT ON DELETE CASCADE,
                    CONSTRAINT fk_company_user_permissions_company
                        FOREIGN KEY (company_id)
                        REFERENCES companies (id)
                        ON UPDATE RESTRICT ON DELETE CASCADE,
                    CONSTRAINT fk_company_user_permissions_permission
                        FOREIGN KEY (permission_id)
                        REFERENCES permissions (id)
                        ON UPDATE RESTRICT ON DELETE CASCADE,
                    CONSTRAINT fk_company_user_permissions_granted_by
                        FOREIGN KEY (granted_by)
                        REFERENCES usuario (id_usuario)
                        ON UPDATE RESTRICT ON DELETE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
        }

        if ($this->tableExists($connection, 'roles')
            && $this->tableExists($connection, 'permissions')
            && $this->tableExists($connection, 'role_permissions')) {
            $this->ensureFinanceRole($connection);
        }
    }

    private function ensureFinanceRole(PDO $connection): void
    {
        $connection->exec(
            "INSERT INTO roles (
                slug, name, label, icon_slug, description, display_priority
            )
            SELECT 'finance', 'FINANCE', 'Financeiro', 'clipboard-check',
                   'Consulta e ajustes financeiros.', 25
            WHERE NOT EXISTS (
                SELECT 1 FROM roles WHERE slug = 'finance'
            )"
        );

        $connection->exec(
            "INSERT INTO role_permissions (role_id, permission_id)
            SELECT r.id, p.id
            FROM roles r
            INNER JOIN permissions p
                ON p.slug IN (
                    'dashboard.view',
                    'finance.view',
                    'finance.adjust',
                    'financial.view',
                    'profile.password.update',
                    'profile.photo.update'
                )
            LEFT JOIN role_permissions rp
                ON rp.role_id = r.id
               AND rp.permission_id = p.id
            WHERE r.slug = 'finance'
              AND rp.role_id IS NULL"
        );
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

        $actionCheckIsCurrent = $this->checkConstraintContains(
            $connection,
            'chk_audit_logs_action',
            'SYSTEMATIC_TENANT_SCAN_DETECTED'
        );

        if ($this->constraintExists($connection, 'audit_logs', 'chk_audit_logs_action')
            && !$actionCheckIsCurrent) {
            $connection->exec('ALTER TABLE audit_logs DROP CHECK chk_audit_logs_action');
        }

        if (!$actionCheckIsCurrent) {
            $connection->exec(
                "ALTER TABLE audit_logs
                    ADD CONSTRAINT chk_audit_logs_action
                    CHECK (
                        action IN (
                            'CREATE',
                            'UPDATE',
                            'DELETE',
                            'UNAUTHORIZED_ACCESS_ATTEMPT',
                            'ACCESS_REVOKED',
                            'USER_PERMISSIONS_UPDATED',
                            'FINANCIAL_ADJUSTMENT',
                            'CROSS_TENANT_ACCESS_ATTEMPT',
                            'SYSTEMATIC_TENANT_SCAN_DETECTED'
                        )
                    )"
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

    private function checkConstraintContains(PDO $connection, string $constraint, string $needle): bool
    {
        $statement = $connection->prepare(
            'SELECT CHECK_CLAUSE
             FROM information_schema.CHECK_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND CONSTRAINT_NAME = :constraint_name
             LIMIT 1'
        );
        $statement->execute(['constraint_name' => $constraint]);
        $clause = $statement->fetchColumn();

        return is_string($clause) && str_contains($clause, $needle);
    }
}
