<?php

declare(strict_types=1);

$root = dirname(__DIR__);
foreach (['Application', 'Domain', 'Infrastructure', 'Presentation'] as $layer) {
    if (!is_dir($root . '/app/Identity/' . $layer)) {
        throw new RuntimeException('Camada ausente em Identity: ' . $layer);
    }
}
foreach (['Services', 'Repositories', 'Events'] as $legacy) {
    if (is_dir($root . '/app/Identity/' . $legacy)) {
        throw new RuntimeException('Diretório técnico legado ainda existe: ' . $legacy);
    }
}
$domainFiles = glob($root . '/app/Identity/Domain/Events/*.php') ?: [];
foreach ($domainFiles as $file) {
    $source = (string) file_get_contents($file);
    foreach (['Infrastructure', 'Presentation', 'PDO'] as $forbidden) {
        if (str_contains($source, $forbidden)) {
            throw new RuntimeException('Domínio Identity depende de ' . $forbidden);
        }
    }
}
$routes = (string) file_get_contents($root . '/routes/web.php');
foreach (['IdentityManagementController', 'AdminUserProvisioningController'] as $controller) {
    if (!str_contains($routes, 'App\\Identity\\Presentation\\' . $controller)) {
        throw new RuntimeException('Rota não importa adapter Identity: ' . $controller);
    }
}
echo "Identity module boundary test passed\n";
