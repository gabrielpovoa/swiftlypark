<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Authorization\Services\CompanyAccessGuard;
use App\Context\RequestIdentity;
use App\Exceptions\ForbiddenException;

$connection = new PDO('sqlite::memory:');
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$connection->exec('CREATE TABLE roles (id INTEGER PRIMARY KEY, slug TEXT, is_active INTEGER)');
$connection->exec('CREATE TABLE user_roles (user_id INTEGER, role_id INTEGER)');
$connection->exec('CREATE TABLE companies (id INTEGER PRIMARY KEY, deleted_at TEXT NULL)');
$connection->exec('CREATE TABLE company_user (user_id INTEGER, company_id INTEGER, role_id INTEGER)');
$connection->exec("INSERT INTO roles VALUES (1, 'admin', 1), (2, 'super-admin', 1)");
$connection->exec('INSERT INTO companies VALUES (5, NULL), (9, NULL)');
$connection->exec('INSERT INTO company_user VALUES (12, 9, 1)');
$connection->exec('INSERT INTO user_roles VALUES (1, 2)');

$identity = static fn (int $id, array $roles): RequestIdentity => new RequestIdentity(
    $id,
    "user{$id}@example.test",
    '127.0.0.1',
    '00000000-0000-4000-8000-' . str_pad((string) $id, 12, '0', STR_PAD_LEFT),
    new DateTimeImmutable('2026-07-19 00:00:00', new DateTimeZone('UTC')),
    ['identity.manage'],
    $roles,
    ['role' => $roles[0], 'label' => $roles[0], 'icon' => 'shield']
);

$adminGuard = new CompanyAccessGuard($connection, $identity(12, ['admin']));
$adminGuard->assertCanManage(9);

try {
    $adminGuard->assertCanManage(5);
    throw new RuntimeException('Administrador acessou empresa sem vínculo.');
} catch (ForbiddenException) {
}

$superAdminGuard = new CompanyAccessGuard($connection, $identity(1, ['super-admin']));
$superAdminGuard->assertCanManage(5);
$superAdminGuard->assertCanManage(9);

echo "Company access guard test passed\n";
