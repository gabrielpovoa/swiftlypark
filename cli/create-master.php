<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';

loadEnv(__DIR__ . '/../.env');

use App\Bootstrap\TenantBootstrap;
use App\Identity\Services\UserProvisioningService;
use Config\Database;

$options = getopt('', [
    'name:',
    'email:',
    'password:',
    'company-slug::',
    'company-name::',
]);

$name = trim((string) ($options['name'] ?? ''));
$email = trim((string) ($options['email'] ?? ''));
$password = (string) ($options['password'] ?? '');
$companySlug = strtolower(trim((string) ($options['company-slug'] ?? 'swiftlypark')));
$companyName = trim((string) ($options['company-name'] ?? 'SwiftlyPark'));

if ($name === '' || $email === '' || $password === '') {
    fwrite(
        STDERR,
        "Uso: php cli/create-master.php --name=\"Master\" --email=\"master@swiftlypark.com\" --password=\"SenhaForte123!\"\n"
    );
    exit(1);
}

$connection = (new Database())->connect();
(new TenantBootstrap($connection))->boot();

$activeMaster = $connection->query(
    "SELECT COUNT(DISTINCT u.id_usuario)
     FROM usuario u
     INNER JOIN user_roles ur ON ur.user_id = u.id_usuario
     INNER JOIN roles r ON r.id = ur.role_id
     WHERE r.slug = 'master'
       AND u.deleted_at IS NULL"
)->fetchColumn();

if ((int) $activeMaster > 0) {
    fwrite(STDERR, "Já existe um usuário Master ativo. Operação abortada.\n");
    exit(1);
}

$company = $connection->prepare(
    'SELECT id FROM companies WHERE slug = :slug LIMIT 1'
);
$company->execute(['slug' => $companySlug]);
$companyId = $company->fetchColumn();

if ($companyId === false) {
    $insertCompany = $connection->prepare(
        'INSERT INTO companies (name, slug, created_at, updated_at)
         VALUES (:name, :slug, NOW(), NOW())'
    );
    $insertCompany->execute([
        'name' => $companyName,
        'slug' => $companySlug,
    ]);
    $companyId = (int) $connection->lastInsertId();
}

$role = $connection->prepare(
    "SELECT id FROM roles WHERE slug = 'master' AND is_active = 1 LIMIT 1"
);
$role->execute();
$roleId = $role->fetchColumn();

if ($roleId === false) {
    fwrite(STDERR, "Papel master não encontrado. Execute as migrations RBAC antes.\n");
    exit(1);
}

$userId = (new UserProvisioningService($connection))->create(
    $name,
    $email,
    $password,
    (int) $companyId,
    (int) $roleId
);

echo "Master inicial criado com id {$userId}.\n";
