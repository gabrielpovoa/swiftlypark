<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Infrastructure\Database\MigrationRunner;
use Config\Database;

$path = $argv[1] ?? '';
if ($path === '' || !is_dir($path)) {
    fwrite(STDERR, "Diretório temporário de migrations inválido.\n");
    exit(1);
}

$executed = (new MigrationRunner((new Database())->connect(), $path))->migrate();
fwrite(STDOUT, sprintf("CI aplicou %d migration(s).\n", count($executed)));
