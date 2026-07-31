<?php

declare(strict_types=1);

$root = __DIR__;
$environment = getenv('PHINX_ENVIRONMENT') ?: 'development';

$database = [
    'adapter' => 'mysql',
    'host' => getenv('DB_HOST') ?: 'localhost',
    'name' => getenv('DB_DATABASE') ?: 'parking',
    'user' => getenv('DB_USERNAME') ?: 'root',
    'pass' => getenv('DB_PASSWORD') ?: '',
    'port' => (int) (getenv('DB_PORT') ?: 3306),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];

return [
    'paths' => [
        'migrations' => $root . '/database/phinx',
        'seeds' => $root . '/database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => $environment,
        'development' => $database,
        'testing' => $database,
        'production' => $database,
    ],
    'version_order' => 'creation',
];
