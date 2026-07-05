<?php

declare(strict_types=1);

use App\Repositories\PasswordResetRepository;
use Config\Database;

require dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/env.php';

loadEnv(dirname(__DIR__) . '/.env');

$connection = (new Database())->connect();
$repository = new PasswordResetRepository($connection);
$removedRows = $repository->deleteExpired();

fwrite(STDOUT, sprintf("%d registro(s) expirado(s) removido(s).\n", $removedRows));
