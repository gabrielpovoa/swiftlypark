<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/env.php';

loadEnv(dirname(__DIR__) . '/.env');
date_default_timezone_set('UTC');

use App\Services\PasswordRecoveryMailer;
use App\Services\CheckoutReceiptMailer;
use App\Shared\Application\JobWorker;
use App\Shared\Infrastructure\Queue\PdoJobQueue;
use App\Shared\Infrastructure\Security\EncryptedPayload;
use Config\Database;
$once = in_array('--once', $argv, true);
$worker = new JobWorker(new PdoJobQueue((new Database())->connect()),
    EncryptedPayload::fromEnvironment(), new PasswordRecoveryMailer(), new CheckoutReceiptMailer());
do {
    $processed = $worker->runOnce('mail');
    if ($once) break;
    if (!$processed) sleep(2);
} while (true);
