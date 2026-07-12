<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Context\IdentityContext;
use App\Context\RequestIdentity;
use App\Context\TenantContext;
use App\Exceptions\SecurityCriticalException;
use App\Middleware\TenantMiddleware;

ini_set('session.save_path', sys_get_temp_dir());

$connection = new PDO('sqlite::memory:');
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$connection->exec(
    'CREATE TABLE companies (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        slug TEXT NOT NULL,
        logo_path TEXT NULL,
        deleted_at TEXT NULL
    )'
);
$connection->exec(
    'CREATE TABLE usuario (
        id_usuario INTEGER PRIMARY KEY,
        email TEXT NOT NULL,
        deleted_at TEXT NULL
    )'
);
$connection->exec(
    'CREATE TABLE company_user (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        company_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        role_id INTEGER NULL,
        created_at TEXT NOT NULL
    )'
);
$connection->exec(
    'CREATE TABLE roles (
        id INTEGER PRIMARY KEY,
        slug TEXT NOT NULL,
        label TEXT NOT NULL,
        is_active INTEGER NOT NULL
    )'
);
$connection->exec(
    'CREATE TABLE audit_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        company_id INTEGER NULL,
        actor_email TEXT NOT NULL,
        action TEXT NOT NULL,
        entity TEXT NOT NULL,
        entity_id TEXT NOT NULL,
        old_values TEXT NULL,
        new_values TEXT NULL,
        ip_address TEXT NOT NULL,
        request_id TEXT NOT NULL,
        created_at TEXT NOT NULL
    )'
);

$connection->exec("INSERT INTO companies (id, name, slug) VALUES (1, 'Empresa A', 'empresa-a')");
$connection->exec("INSERT INTO companies (id, name, slug) VALUES (2, 'Empresa B', 'empresa-b')");
$connection->exec("INSERT INTO usuario (id_usuario, email) VALUES (10, 'admin-a@example.test')");
$connection->exec("INSERT INTO roles (id, slug, label, is_active) VALUES (1, 'admin', 'Admin', 1)");
$connection->exec("INSERT INTO company_user (company_id, user_id, role_id, created_at) VALUES (1, 10, 1, '2026-07-12 00:00:00.000000')");

$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
$_SERVER['HTTP_X_COMPANY_ID'] = '2';
$_GET['url'] = 'vacancy';
$_SESSION = ['user_id' => 10, 'company_id' => 1];

TenantContext::instance()->clear();
IdentityContext::clear();
IdentityContext::set(new RequestIdentity(
    10,
    'admin-a@example.test',
    '203.0.113.10',
    '00000000-0000-4000-8000-000000000001',
    new DateTimeImmutable('2026-07-12 00:00:00', new DateTimeZone('UTC')),
    ['vehicle.view'],
    ['admin'],
    ['role' => 'admin', 'label' => 'Admin', 'icon' => 'shield']
));

$middleware = new TenantMiddleware(TenantContext::instance(), $connection);
$blocked = false;

try {
    $middleware->handle(static function (): void {
        fwrite(STDERR, "Cross-tenant request unexpectedly reached controller\n");
        exit(1);
    });
} catch (SecurityCriticalException $exception) {
    $blocked = $exception->securityCode() === SecurityCriticalException::CODE_CROSS_TENANT_ACCESS;
}

if (!$blocked) {
    fwrite(STDERR, "Expected cross-tenant access to be blocked with 403/security critical semantics\n");
    exit(1);
}

$statement = $connection->query(
    "SELECT action, new_values
     FROM audit_logs
     WHERE action = 'CROSS_TENANT_ACCESS_ATTEMPT'
     LIMIT 1"
);
$audit = $statement->fetch(PDO::FETCH_ASSOC);

if ($audit === false) {
    fwrite(STDERR, "Expected CROSS_TENANT_ACCESS_ATTEMPT audit log\n");
    exit(1);
}

$payload = json_decode((string) $audit['new_values'], true);
if (($payload['severity'] ?? null) !== 'CRITICAL'
    || ($payload['ip_address'] ?? null) !== '203.0.113.10'
    || ($payload['user_id'] ?? null) !== 10
    || ($payload['route'] ?? null) !== 'vacancy'
    || ($payload['attempted_company_id'] ?? null) !== 2) {
    fwrite(STDERR, "Cross-tenant audit payload is incomplete\n");
    exit(1);
}

echo "Security test suite passed\n";
