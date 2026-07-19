<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$view = file_get_contents($root . '/app/Views/Identity/index.php');
$controller = file_get_contents(
    $root . '/app/Identity/Presentation/IdentityManagementController.php'
);
$repository = file_get_contents(
    $root . '/app/Identity/Infrastructure/IdentityManagementRepository.php'
);

if (in_array(false, [$view, $controller, $repository], true)) {
    throw new RuntimeException('Não foi possível carregar a listagem de identidade.');
}

foreach ([
    "\$_GET['status'] ?? 'active'",
    ": 'active';",
    '$status !== \'active\'',
] as $marker) {
    if (!str_contains($controller, $marker)) {
        throw new RuntimeException('Status ativo não é o padrão da consulta: ' . $marker);
    }
}

foreach ([
    "'status' => 'active'",
    "xl:grid-cols-2",
    'Usuários ativos',
    "=== 'active'",
] as $marker) {
    if (!str_contains($view, $marker)) {
        throw new RuntimeException('Grid ou paginação por status incompleto: ' . $marker);
    }
}

if (!str_contains($repository, "u.deleted_at IS NULL")
    || !str_contains($repository, "u.deleted_at IS NOT NULL")) {
    throw new RuntimeException('Repository não diferencia usuários ativos e revogados.');
}

echo "Identity user grid test passed\n";
