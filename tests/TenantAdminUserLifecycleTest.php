<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$migration = file_get_contents(
    $root . '/database/migrations/20260731_grant_admin_identity_management.sql'
);
$controller = file_get_contents(
    $root . '/app/Identity/Presentation/IdentityManagementController.php'
);
$service = file_get_contents(
    $root . '/app/Identity/Application/IdentityManagementService.php'
);
$provisioning = file_get_contents(
    $root . '/app/Identity/Application/UserProvisioningService.php'
);

if (in_array(false, [$migration, $controller, $service, $provisioning], true)) {
    throw new RuntimeException('Não foi possível carregar o ciclo de usuários do tenant.');
}

foreach (['identity.view', 'identity.manage', "r.slug = 'admin'"] as $marker) {
    if (!str_contains($migration, $marker)) {
        throw new RuntimeException('Permissão administrativa ausente na migration: ' . $marker);
    }
}

foreach ([
    "\$filters['exclude_super_admin'] = !\$isSuperAdmin",
    'TenantContext::instance()->getCompanyId()',
    'new UserProvisioningService(',
] as $marker) {
    if (!str_contains($controller, $marker)) {
        throw new RuntimeException('Tela de identidade sem escopo seguro: ' . $marker);
    }
}

if (!str_contains($service, 'assertTargetInScope($targetUserId)')
    || !str_contains($provisioning, 'assertCanManageCompany($companyId)')) {
    throw new RuntimeException('Ações do ciclo de acesso não validam o tenant.');
}

echo "Tenant admin user lifecycle test passed\n";
