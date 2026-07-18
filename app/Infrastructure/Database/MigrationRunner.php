<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use DomainException;
use PDO;
use RuntimeException;

final class MigrationRunner
{
    public function __construct(
        private PDO $connection,
        private string $migrationsPath
    ) {
    }

    public function migrate(): array
    {
        $this->ensureMetadataTable();
        $applied = $this->appliedMigrations();
        $executed = [];

        foreach ($this->migrationFiles() as $version => $path) {
            $checksum = hash_file('sha256', $path);
            if ($checksum === false) {
                throw new RuntimeException('Não foi possível calcular o checksum de ' . $version);
            }
            if (isset($applied[$version])) {
                if (!hash_equals($applied[$version], $checksum)) {
                    throw new DomainException(
                        sprintf('A migration aplicada %s foi modificada.', $version)
                    );
                }
                continue;
            }

            $sql = file_get_contents($path);
            if ($sql === false || trim($sql) === '') {
                throw new RuntimeException('Migration vazia ou ilegível: ' . $version);
            }

            $this->connection->exec($sql);
            $this->record($version, $checksum);
            $executed[] = $version;
        }

        return $executed;
    }

    public function baseline(): array
    {
        $this->ensureMetadataTable();
        if ($this->appliedMigrations() !== []) {
            throw new DomainException('O baseline só pode ser criado sem migrations registradas.');
        }

        $recorded = [];
        foreach ($this->migrationFiles() as $version => $path) {
            $checksum = hash_file('sha256', $path);
            if ($checksum === false) {
                throw new RuntimeException('Não foi possível calcular o checksum de ' . $version);
            }
            $this->record($version, $checksum);
            $recorded[] = $version;
        }

        return $recorded;
    }

    public function status(): array
    {
        $this->ensureMetadataTable();
        $applied = $this->appliedMigrations();

        return array_map(
            static fn (string $version): array => [
                'version' => $version,
                'applied' => isset($applied[$version]),
            ],
            array_keys($this->migrationFiles())
        );
    }

    private function ensureMetadataTable(): void
    {
        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(255) NOT NULL,
                checksum CHAR(64) NOT NULL,
                applied_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                PRIMARY KEY (version)
            ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
    }

    private function migrationFiles(): array
    {
        $files = glob(rtrim($this->migrationsPath, '/') . '/*.sql') ?: [];
        sort($files, SORT_STRING);
        $migrations = [];

        foreach ($files as $path) {
            $version = basename($path);
            if (isset($migrations[$version])) {
                throw new DomainException('Migration duplicada: ' . $version);
            }
            $migrations[$version] = $path;
        }

        return $migrations;
    }

    private function appliedMigrations(): array
    {
        $rows = $this->connection
            ->query('SELECT version, checksum FROM schema_migrations ORDER BY version')
            ->fetchAll(PDO::FETCH_ASSOC);

        $applied = [];
        foreach ($rows as $row) {
            $applied[(string) $row['version']] = (string) $row['checksum'];
        }

        return $applied;
    }

    private function record(string $version, string $checksum): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO schema_migrations (version, checksum) VALUES (:version, :checksum)'
        );
        $statement->execute(['version' => $version, 'checksum' => $checksum]);
    }
}
