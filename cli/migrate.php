<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Config\Database;

$root = dirname(__DIR__);
$action = strtolower((string) ($argv[1] ?? 'migrate'));
$supported = ['migrate', 'status', 'rollback', 'create'];

if (!in_array($action, $supported, true)) {
    fwrite(STDERR, "Uso: php cli/migrate.php migrate|status|rollback|create [opções]\n");
    exit(1);
}

$environment = getenv('PHINX_ENVIRONMENT') ?: 'development';
$command = [
    PHP_BINARY,
    $root . '/vendor/bin/phinx',
    $action,
    '--configuration=' . $root . '/phinx.php',
    '--environment=' . $environment,
];

foreach (array_slice($argv, 2) as $argument) {
    $command[] = (string) $argument;
}

$lockConnection = null;
$lockName = 'swiftlypark:migrations:' . (getenv('DB_DATABASE') ?: 'parking');

try {
    if (in_array($action, ['migrate', 'rollback'], true)) {
        $lockConnection = (new Database())->connect();
        $configuredTimeout = getenv('MIGRATION_LOCK_TIMEOUT');
        $lock = $lockConnection->prepare('SELECT GET_LOCK(:lock_name, :timeout_seconds)');
        $lock->execute([
            'lock_name' => $lockName,
            'timeout_seconds' => max(
                0,
                $configuredTimeout === false ? 10 : (int) $configuredTimeout
            ),
        ]);
        if ((int) $lock->fetchColumn() !== 1) {
            throw new RuntimeException(
                'Outra execução de migration está em andamento para este banco.'
            );
        }
    }

    $process = proc_open(
        $command,
        [STDIN, STDOUT, STDERR],
        $pipes,
        $root
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Não foi possível iniciar o Phinx.');
    }

    $exitCode = proc_close($process);
    exit($exitCode);
} catch (Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
    exit(1);
} finally {
    if ($lockConnection instanceof PDO) {
        $release = $lockConnection->prepare('SELECT RELEASE_LOCK(:lock_name)');
        $release->execute(['lock_name' => $lockName]);
    }
}
