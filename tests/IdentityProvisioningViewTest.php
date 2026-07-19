<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$identityView = file_get_contents($root . '/app/Views/Identity/index.php');
$governanceView = file_get_contents($root . '/app/Views/Admin/provisioning.php');
$controller = file_get_contents(
    $root . '/app/Identity/Presentation/IdentityManagementController.php'
);
$repository = file_get_contents(
    $root . '/app/Identity/Infrastructure/IdentityManagementRepository.php'
);
$routes = file_get_contents($root . '/routes/web.php');

if (in_array(false, [
    $identityView,
    $governanceView,
    $controller,
    $repository,
    $routes,
], true)) {
    throw new RuntimeException('Não foi possível carregar o fluxo de provisionamento.');
}

foreach ([
    'Adicionar novo usuário',
    'action="/identity/create"',
    '$canManageIdentity',
    '$provisioningFormOpen',
] as $marker) {
    if (!str_contains($identityView, $marker)) {
        throw new RuntimeException('Formulário de identidade incompleto: ' . $marker);
    }
}

if (str_contains($governanceView, 'action="/admin/users/create"')
    || str_contains($governanceView, 'Adicionar novo usuário')) {
    throw new RuntimeException('A criação de usuário ainda aparece na Governança SaaS.');
}

if (!str_contains($routes, "post('identity/create'")
    || !str_contains($routes, "permissionRequired('identity.manage', 'identity/create'")) {
    throw new RuntimeException('Endpoint de criação não está protegido por identity.manage.');
}

foreach ([
    'new UserProvisioningService(',
    'new AuditService(',
    'hasValidCsrfToken()',
] as $marker) {
    if (!str_contains($controller, $marker)) {
        throw new RuntimeException('Controller não preserva o provisionamento seguro: ' . $marker);
    }
}

if (!str_contains($repository, 'function activeCompanies(')
    || !str_contains($repository, 'function assignableRoles(')) {
    throw new RuntimeException('Opções seguras de empresa e perfil não foram disponibilizadas.');
}

echo "Identity provisioning view test passed\n";
