<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Infrastructure\Database\MigrationRunner;
use Config\Database;

$command = $argv[1] ?? 'migrate';
$runner = new MigrationRunner(
    (new Database())->connect(),
    dirname(__DIR__) . '/database/migrations'
);

try {
    if ($command === 'baseline') {
        $versions = $runner->baseline();
        fwrite(STDOUT, sprintf("Baseline criado com %d migrations.\n", count($versions)));
        exit(0);
    }
    if ($command === 'status') {
        foreach ($runner->status() as $migration) {
            fwrite(STDOUT, sprintf(
                "[%s] %s\n",
                $migration['applied'] ? 'x' : ' ',
                $migration['version']
            ));
        }
        exit(0);
    }
    if ($command !== 'migrate') {
        throw new InvalidArgumentException('Use: migrate, status ou baseline.');
    }

    $versions = $runner->migrate();
    fwrite(STDOUT, $versions === []
        ? "Banco atualizado; nenhuma migration pendente.\n"
        : sprintf("%d migration(s) aplicada(s): %s\n", count($versions), implode(', ', $versions)));
} catch (Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
    exit(1);
}
