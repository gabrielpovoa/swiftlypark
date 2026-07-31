<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$composer = json_decode((string) file_get_contents($root . '/composer.json'), true);
$config = (string) file_get_contents($root . '/phinx.php');
$baseline = (string) file_get_contents(
    $root . '/database/phinx/20260701000000_baseline_current_schema.php'
);
$cli = (string) file_get_contents($root . '/cli/migrate.php');
$integration = (string) file_get_contents($root . '/tests/ci/run-integration.sh');

if (!isset($composer['require']['robmorgan/phinx'])) {
    throw new RuntimeException('Phinx não está declarado como dependência operacional.');
}

foreach ([
    "getenv('DB_HOST')",
    "getenv('DB_DATABASE')",
    "'default_migration_table' => 'phinxlog'",
    "'version_order' => 'creation'",
] as $marker) {
    if (!str_contains($config, $marker)) {
        throw new RuntimeException('Configuração Phinx incompleta: ' . $marker);
    }
}

foreach ([
    'REQUIRED_TABLES',
    'REQUIRED_COLUMNS',
    'assertCurrentSchema',
    'removeLegacySampleData',
    'IrreversibleMigrationException',
] as $marker) {
    if (!str_contains($baseline, $marker)) {
        throw new RuntimeException('Baseline formal incompleto: ' . $marker);
    }
}

foreach (['GET_LOCK', 'RELEASE_LOCK', "['migrate', 'status', 'rollback', 'create']"] as $marker) {
    if (!str_contains($cli, $marker)) {
        throw new RuntimeException('CLI formal incompleta: ' . $marker);
    }
}

if (str_contains($integration, 'tests/ci/migrate.php')
    || !str_contains($integration, 'php cli/migrate.php migrate')) {
    throw new RuntimeException('CI ainda utiliza o runner artesanal de migrations.');
}

if (is_file($root . '/app/Infrastructure/Database/MigrationRunner.php')) {
    throw new RuntimeException('O runner artesanal ainda está ativo.');
}

echo "Formal database migrations test passed\n";
