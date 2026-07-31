<?php

declare(strict_types=1);

use App\Infrastructure\Database\SqlScriptExecutor;
use Phinx\Migration\AbstractMigration;
use Phinx\Migration\IrreversibleMigrationException;

final class BaselineCurrentSchema extends AbstractMigration
{
    private const REQUIRED_TABLES = [
        'audit_logs',
        'background_jobs',
        'companies',
        'company_user',
        'company_user_permissions',
        'company_vehicle_types',
        'financial_adjustments',
        'financial_ledger_entries',
        'login',
        'monthly_contract_payments',
        'monthly_contracts',
        'parking_active_stays',
        'password_resets',
        'permissions',
        'role_permissions',
        'roles',
        'tarifarios',
        'transacoes',
        'user_permissions',
        'user_roles',
        'usuario',
        'vagas_disponiveis',
        'vagas_preenchidas',
    ];

    private const REQUIRED_COLUMNS = [
        'companies' => ['legal_name', 'trade_name', 'cnpj', 'logo_path', 'is_mensalista'],
        'company_user' => ['company_id', 'user_id', 'role_id'],
        'transacoes' => ['company_id', 'payment_method', 'payment_date'],
        'vagas_disponiveis' => ['company_id'],
        'vagas_preenchidas' => ['company_id', 'billing_model'],
        'usuario' => ['deleted_at', 'password_reset_required'],
    ];

    public function up(): void
    {
        $connection = $this->connection();
        $existingTables = $this->applicationTables($connection);

        if ($existingTables !== []) {
            $this->assertCurrentSchema($connection, $existingTables);
            return;
        }

        $root = dirname(__DIR__, 2);
        $executor = new SqlScriptExecutor($connection);

        $connection->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            $this->applyLegacySchema(
                $executor,
                $root . '/database/legacy/heidisql/parking.sql'
            );
            $financialLegacy = file_get_contents(
                $root . '/database/legacy/heidisql/migration-fin-001-payment-date.sql'
            );
            if ($financialLegacy === false) {
                throw new \RuntimeException('Migration financeira legada não pôde ser lida.');
            }
            $executor->execute((string) preg_replace(
                '/^\s*USE\s+[^;]+;\s*$/mi',
                '',
                $financialLegacy
            ));

            $migrationFiles = glob($root . '/database/migrations/*.sql') ?: [];
            sort($migrationFiles, SORT_STRING);
            foreach ($migrationFiles as $migrationFile) {
                // Esta correção de vínculo é desnecessária em uma instalação
                // limpa e a forma histórica não é aceita por todo MySQL 8.
                if (basename($migrationFile)
                    === '20260719_remove_unintended_swiftlypark_default_memberships.sql') {
                    continue;
                }
                $executor->executeFile($migrationFile);
            }

            $this->ensureAuthorizationReferenceData($connection);
            $this->removeLegacySampleData($connection);
        } finally {
            $connection->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        $this->assertCurrentSchema($connection, $this->applicationTables($connection));
    }

    public function down(): void
    {
        throw new IrreversibleMigrationException(
            'O baseline não pode remover o schema completo. Restaure um backup para reverter.'
        );
    }

    private function connection(): PDO
    {
        $connection = $this->getAdapter()->getConnection();
        if (!$connection instanceof PDO) {
            throw new \RuntimeException('A conexão PDO do Phinx não está disponível.');
        }

        return $connection;
    }

    /**
     * @return list<string>
     */
    private function applicationTables(\PDO $connection): array
    {
        $statement = $connection->query(
            "SELECT table_name
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_type = 'BASE TABLE'
               AND table_name NOT IN ('phinxlog', 'schema_migrations')
             ORDER BY table_name"
        );

        return array_map(
            'strval',
            $statement->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    /**
     * @param list<string> $existingTables
     */
    private function assertCurrentSchema(\PDO $connection, array $existingTables): void
    {
        $missingTables = array_values(array_diff(self::REQUIRED_TABLES, $existingTables));
        if ($missingTables !== []) {
            throw new \RuntimeException(
                'Banco existente está incompleto; baseline recusado. Tabelas ausentes: '
                . implode(', ', $missingTables)
            );
        }

        $columnStatement = $connection->prepare(
            'SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = :table_name
               AND column_name = :column_name'
        );

        foreach (self::REQUIRED_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $columnStatement->execute([
                    'table_name' => $table,
                    'column_name' => $column,
                ]);
                if ((int) $columnStatement->fetchColumn() !== 1) {
                    throw new \RuntimeException(
                        "Banco existente está incompleto; coluna ausente: {$table}.{$column}"
                    );
                }
            }
        }

        $requiredRoles = $connection->query(
            "SELECT COUNT(*) FROM roles
             WHERE slug IN ('admin', 'auditor', 'operator', 'super-admin', 'finance')"
        )->fetchColumn();
        if ((int) $requiredRoles !== 5) {
            throw new \RuntimeException(
                'Banco existente está incompleto; papéis obrigatórios ausentes.'
            );
        }

        $requiredPermissions = $connection->query(
            "SELECT COUNT(*) FROM permissions
             WHERE slug IN (
                'dashboard.view', 'vehicle.view', 'vehicle.checkin', 'vehicle.checkout',
                'vacancy.create', 'report.view', 'audit.view', 'profile.password.update',
                'profile.photo.update', 'identity.view', 'identity.manage',
                'financial.view', 'finance.view', 'finance.adjust'
             )"
        )->fetchColumn();
        if ((int) $requiredPermissions !== 14) {
            throw new \RuntimeException(
                'Banco existente está incompleto; permissões obrigatórias ausentes.'
            );
        }
    }

    private function removeLegacySampleData(\PDO $connection): void
    {
        $tables = [
            'background_jobs',
            'monthly_contract_payments',
            'monthly_contracts',
            'financial_ledger_entries',
            'financial_adjustments',
            'transacoes',
            'parking_active_stays',
            'vagas_preenchidas',
            'vagas_disponiveis',
            'tarifarios',
            'audit_logs',
            'password_resets',
            'company_user_permissions',
            'company_vehicle_types',
            'company_user',
            'companies',
            'user_permissions',
            'user_roles',
            'usuario',
            'login',
        ];

        foreach ($tables as $table) {
            $connection->exec('DELETE FROM `' . $table . '`');
        }

        foreach ([
            'audit_logs',
            'background_jobs',
            'companies',
            'company_user',
            'financial_adjustments',
            'financial_ledger_entries',
            'login',
            'monthly_contract_payments',
            'monthly_contracts',
            'password_resets',
            'tarifarios',
            'transacoes',
            'usuario',
            'vagas_disponiveis',
            'vagas_preenchidas',
        ] as $table) {
            $connection->exec('ALTER TABLE `' . $table . '` AUTO_INCREMENT = 1');
        }
    }

    private function ensureAuthorizationReferenceData(\PDO $connection): void
    {
        $connection->exec(
            "INSERT INTO roles (
                slug, name, label, icon_slug, description, display_priority, is_active
             ) VALUES
                ('super-admin', 'SUPER_ADMIN', 'Super Admin', 'shield-alert',
                 'Nível máximo da plataforma: administra papéis globais e governança completa.',
                 0, 1),
                ('finance', 'FINANCE', 'Financeiro', 'clipboard-check',
                 'Consulta e ajustes financeiros.', 25, 1)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name), label = VALUES(label), icon_slug = VALUES(icon_slug),
                description = VALUES(description), display_priority = VALUES(display_priority),
                is_active = VALUES(is_active)"
        );

        $connection->exec(
            "INSERT IGNORE INTO role_permissions (role_id, permission_id)
             SELECT role.id, permission.id
             FROM roles role
             CROSS JOIN permissions permission
             WHERE role.slug = 'super-admin'
                OR (role.slug = 'finance' AND permission.slug IN (
                    'dashboard.view', 'profile.password.update', 'profile.photo.update',
                    'financial.view', 'finance.view', 'finance.adjust'
                ))"
        );
    }

    private function applyLegacySchema(
        SqlScriptExecutor $executor,
        string $path
    ): void {
        $sql = file_get_contents($path);
        if ($sql === false || trim($sql) === '') {
            throw new \RuntimeException('Schema legado não pôde ser lido.');
        }

        foreach ($executor->statements($sql) as $statement) {
            if (preg_match('/^\s*(INSERT\s+INTO|LOCK\s+TABLES|UNLOCK\s+TABLES)\b/i', $statement)
                === 1) {
                continue;
            }
            $executor->execute($statement);
        }
    }
}
