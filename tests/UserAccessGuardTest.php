<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Authorization\Services\UserAccessGuard;
use App\Context\RequestIdentity;
use App\Context\TenantContext;
use App\Exceptions\ForbiddenException;

$connection = new PDO('sqlite::memory:');
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$connection->exec('CREATE TABLE roles (id INTEGER PRIMARY KEY, slug TEXT, is_active INTEGER)');
$connection->exec('CREATE TABLE user_roles (user_id INTEGER, role_id INTEGER)');
$connection->exec('CREATE TABLE company_user (user_id INTEGER, company_id INTEGER, role_id INTEGER)');
$connection->exec("INSERT INTO roles VALUES (1, 'admin', 1), (2, 'super-admin', 1)");
$connection->exec('INSERT INTO user_roles VALUES (1, 2)');
$connection->exec('INSERT INTO company_user VALUES (12, 9, 1), (20, 9, 1), (30, 5, 1)');

$identity = new RequestIdentity(
    12,
    'admin@example.test',
    '127.0.0.1',
    '00000000-0000-4000-8000-000000000012',
    new DateTimeImmutable('2026-07-19 00:00:00', new DateTimeZone('UTC')),
    ['identity.manage'],
    ['admin'],
    ['role' => 'admin', 'label' => 'Administrador', 'icon' => 'shield']
);

$tenantContext = TenantContext::instance();
$tenantContext->setCompany(App\Companies\Domain\Company::reconstitute(9, 'SafePark', 'safepark', null, true));
$guard = new UserAccessGuard($connection, $identity, $tenantContext);
$guard->assertCanManage(20);

foreach ([1, 30] as $blockedUserId) {
    try {
        $guard->assertCanManage($blockedUserId);
        throw new RuntimeException("Usuário protegido ficou acessível: {$blockedUserId}");
    } catch (ForbiddenException) {
    }
}

$tenantContext->clear();
echo "User access guard test passed\n";
