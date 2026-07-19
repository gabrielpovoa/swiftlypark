<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$worker = file_get_contents($root . '/cli/worker.php');
$compose = file_get_contents($root . '/compose.yaml');
$mailer = file_get_contents($root . '/app/Services/PasswordRecoveryMailer.php');

if ($worker === false || $compose === false || $mailer === false) {
    throw new RuntimeException('Não foi possível carregar os arquivos do fluxo de e-mail.');
}

if (
    !str_contains($worker, "config/env.php")
    || !str_contains($worker, "loadEnv(dirname(__DIR__) . '/.env')")
) {
    throw new RuntimeException('O worker deve carregar o ambiente pelo config/env.php.');
}

if (
    preg_match('/^  worker:\R/m', $compose) !== 1
    || !str_contains($compose, 'command: php cli/worker.php')
    || !str_contains($compose, 'restart: always')
) {
    throw new RuntimeException('O worker de e-mail deve ser um serviço persistente do Docker Compose.');
}

foreach (['MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION'] as $variable) {
    if (!str_contains($mailer, "getenv('{$variable}')")) {
        throw new RuntimeException("Mailer não utiliza {$variable} do ambiente.");
    }
}

if (substr_count($mailer, 'isHTML(true)') < 2) {
    throw new RuntimeException('As mensagens com template HTML devem ser enviadas como HTML.');
}

echo "Reliable email delivery test passed\n";
