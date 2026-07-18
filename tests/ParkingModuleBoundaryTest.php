<?php

declare(strict_types=1);

$root = dirname(__DIR__);
foreach (['Application', 'Domain', 'Infrastructure', 'Presentation'] as $layer) {
    if (!is_dir($root . '/app/Parking/' . $layer)) {
        throw new RuntimeException('Camada Parking ausente: ' . $layer);
    }
}
foreach (array_merge(
    glob($root . '/app/Parking/Application/*.php') ?: [],
    glob($root . '/app/Parking/Presentation/*.php') ?: []
) as $file) {
    $source = (string) file_get_contents($file);
    if (preg_match('/\b(?:SELECT|INSERT|UPDATE|DELETE)\s+(?:FROM|INTO|[a-z_])/i', $source) === 1) {
        throw new RuntimeException('SQL fora de Infrastructure: ' . $file);
    }
}
foreach (glob($root . '/app/Parking/Domain/*.php') ?: [] as $file) {
    $source = (string) file_get_contents($file);
    if (str_contains($source, 'Infrastructure') || str_contains($source, 'PDO')) {
        throw new RuntimeException('Domínio Parking depende de infraestrutura.');
    }
}
foreach (['app/Models/VacancyModel.php', 'app/Models/CreateVacancyModel.php',
    'app/Controllers/VacancyController.php', 'app/Controllers/CreateVacancy.php'] as $legacy) {
    if (is_file($root . '/' . $legacy)) {
        throw new RuntimeException('Adapter legado ainda existe: ' . $legacy);
    }
}
$routes = (string) file_get_contents($root . '/routes/web.php');
if (!str_contains($routes, 'App\\Parking\\Presentation\\VacancyController')
    || !str_contains($routes, 'App\\Parking\\Presentation\\CreateVacancyController')) {
    throw new RuntimeException('Rotas não usam Presentation de Parking.');
}
echo "Parking module boundary test passed\n";
