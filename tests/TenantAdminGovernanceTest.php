<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$routes = file_get_contents($root . '/routes/web.php');
$controller = file_get_contents(
    $root . '/app/Identity/Presentation/AdminUserProvisioningController.php'
);
$guard = file_get_contents($root . '/app/Authorization/Services/UserAccessGuard.php');
$provisioning = file_get_contents(
    $root . '/app/Identity/Application/UserProvisioningService.php'
);

if (in_array(false, [$routes, $controller, $guard, $provisioning], true)) {
    throw new RuntimeException('Não foi possível carregar a governança administrativa.');
}

if (!str_contains($routes, "get('admin', permissionRequired('identity.manage'")) {
    throw new RuntimeException('Admin deve acessar Governança SaaS por identity.manage.');
}

foreach ([
    'TenantContext::instance()->getCompanyId()',
    "protected_role.slug = \"super-admin\"",
    "slug <> 'super-admin'",
    'new UserAccessGuard(',
] as $marker) {
    if (!str_contains($controller, $marker)) {
        throw new RuntimeException('Governança sem proteção esperada: ' . $marker);
    }
}

if (!str_contains($guard, 'O perfil do Super-Admin é restrito')) {
    throw new RuntimeException('Ações diretas contra super-admin não estão bloqueadas.');
}

if (!str_contains($provisioning, "\$role['slug'] === 'super-admin'")
    || !str_contains($provisioning, 'Apenas SUPER-ADMIN pode conceder acesso global.')) {
    throw new RuntimeException('Admin ainda pode atribuir papel super-admin.');
}

echo "Tenant admin governance test passed\n";
