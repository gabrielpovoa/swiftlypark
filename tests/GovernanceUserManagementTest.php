<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Context\RequestIdentity;
use App\Contracts\PasswordRecoveryMailerInterface;
use App\Companies\Application\RegisterCompany;
use App\Companies\Infrastructure\PdoCompanyRepository;
use App\Identity\Application\UserProvisioningService;
use App\Services\PasswordGeneratorService;

final class CapturingMailer implements PasswordRecoveryMailerInterface
{
    public ?string $temporaryPassword = null;
    public ?string $reactivationPassword = null;

    public function sendOtp(string $email, string $otp, int $ttlMinutes): void
    {
    }

    public function sendTemporaryPassword(string $email, string $temporaryPassword): void
    {
        $this->temporaryPassword = $temporaryPassword;
    }

    public function sendReactivationPassword(string $email, string $temporaryPassword): void
    {
        $this->reactivationPassword = $temporaryPassword;
    }
}

$passwords = new PasswordGeneratorService();
$generated = $passwords->temporary();

if (strlen($generated) < 8 || preg_match('/^[A-Za-z0-9]+$/', $generated) !== 1) {
    fwrite(STDERR, "Expected alphanumeric temporary password with at least 8 chars\n");
    exit(1);
}

$connection = new PDO('sqlite::memory:');
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$connection->sqliteCreateFunction('NOW', static fn (): string => '2026-07-12 00:00:00');
$connection->exec(
    'CREATE TABLE login (
        id_login INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL,
        senha TEXT NOT NULL
    )'
);
$connection->exec(
    'CREATE TABLE usuario (
        id_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
        id_login INTEGER NOT NULL,
        nome TEXT NOT NULL,
        email TEXT NOT NULL,
        senha_hash TEXT NOT NULL,
        password_reset_required INTEGER NOT NULL DEFAULT 0,
        deleted_at TEXT NULL
    )'
);
$connection->exec(
    'CREATE TABLE companies (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        slug TEXT NOT NULL,
        logo_path TEXT NULL,
        deleted_at TEXT NULL,
        created_at TEXT NULL,
        updated_at TEXT NULL
    )'
);
$connection->exec(
    'CREATE TABLE roles (
        id INTEGER PRIMARY KEY,
        slug TEXT NOT NULL,
        display_priority INTEGER NOT NULL,
        is_active INTEGER NOT NULL
    )'
);
$connection->exec(
    'CREATE TABLE user_roles (
        user_id INTEGER NOT NULL,
        role_id INTEGER NOT NULL,
        created_by INTEGER NULL
    )'
);
$connection->exec(
    'CREATE TABLE company_user (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        company_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        role_id INTEGER NOT NULL,
        created_at TEXT NOT NULL
    )'
);
$connection->exec("INSERT INTO companies (id, name, slug) VALUES (1, 'SwiftlyPark', 'swiftlypark')");
$connection->exec("INSERT INTO companies (id, name, slug) VALUES (2, 'Empresa B', 'empresa-b')");
$connection->exec("INSERT INTO roles (id, slug, display_priority, is_active) VALUES (3, 'admin', 10, 1)");
$connection->exec("INSERT INTO roles (id, slug, display_priority, is_active) VALUES (4, 'master', 1, 1)");

$identity = new RequestIdentity(
    99,
    'master@example.test',
    '127.0.0.1',
    '00000000-0000-4000-8000-000000000099',
    new DateTimeImmutable('2026-07-12 00:00:00', new DateTimeZone('UTC')),
    ['identity.manage'],
    ['master'],
    ['role' => 'master', 'label' => 'Master', 'icon' => 'shield']
);
$mailer = new CapturingMailer();
$service = new UserProvisioningService(
    $connection,
    $identity,
    null,
    null,
    $passwords,
    $mailer
);

foreach ([' ', '  ', '@@@', 'A'] as $invalidName) {
    try {
        $service->create($invalidName, 'invalid-' . md5($invalidName) . '@example.test', 2, 3);
        fwrite(STDERR, "Expected invalid name to be rejected\n");
        exit(1);
    } catch (DomainException) {
    }
}

$userId = $service->create('Jo', 'jo@example.test', 2, 3);

$membershipRows = $connection
    ->query('SELECT user_id, company_id, role_id FROM company_user WHERE user_id = ' . $userId)
    ->fetchAll(PDO::FETCH_ASSOC);
$memberships = array_map(
    static fn (array $row): int => (int) $row['company_id'],
    $membershipRows
);

if ($memberships !== [2]) {
    fwrite(STDERR, "Expected user to be associated exclusively with company 2\n");
    exit(1);
}

$globalRoleCount = (int) $connection
    ->query('SELECT COUNT(*) FROM user_roles WHERE user_id = ' . $userId)
    ->fetchColumn();

if ($globalRoleCount !== 0) {
    fwrite(STDERR, "Expected tenant role not to be persisted as a global platform role\n");
    exit(1);
}

if ($mailer->temporaryPassword === null
    || preg_match('/^[A-Za-z0-9]{8,}$/', $mailer->temporaryPassword) !== 1) {
    fwrite(STDERR, "Expected temporary credentials to be generated and sent\n");
    exit(1);
}

$hash = $connection
    ->query('SELECT senha_hash FROM usuario WHERE id_usuario = ' . $userId)
    ->fetchColumn();

if (!password_verify($mailer->temporaryPassword, (string) $hash)) {
    fwrite(STDERR, "Expected persisted password hash to match generated temporary password\n");
    exit(1);
}

try {
    $service->linkExistingUserToCompany(99, 2, 3);
    fwrite(STDERR, "Expected self company-role mutation to be rejected\n");
    exit(1);
} catch (DomainException) {
}

$companyId = (new RegisterCompany(
    $connection,
    new PdoCompanyRepository($connection),
    $identity
))->execute('Nova Master', 'nova-master');
$actorMembership = $connection
    ->query(
        'SELECT role_id FROM company_user WHERE user_id = 99 AND company_id = '
        . $companyId
    )
    ->fetchColumn();

if ((int) $actorMembership !== 4) {
    fwrite(STDERR, "Expected creator to be linked to the new company with master role\n");
    exit(1);
}

echo "Governance user management test passed\n";
