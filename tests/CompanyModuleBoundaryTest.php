<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$moduleFiles = glob($root . '/app/Companies/*/*.php') ?: [];
if ($moduleFiles === []) {
    throw new RuntimeException('Módulo Companies não foi encontrado.');
}
foreach ($moduleFiles as $file) {
    $source = file_get_contents($file);
    if ($source === false || str_contains($source, 'App\\Identity\\')) {
        throw new RuntimeException('Dependência inválida de Companies: ' . $file);
    }
}
$controller = file_get_contents($root . '/app/Controllers/AdminCompanyController.php');
if ($controller === false || !str_contains($controller, 'PdoCompanyRepository')) {
    throw new RuntimeException('Controller não usa a fronteira Companies.');
}
foreach (['INSERT INTO companies', 'UPDATE companies', 'FROM companies c'] as $sql) {
    if (str_contains($controller, $sql)) {
        throw new RuntimeException('SQL cadastral vazou para o controller: ' . $sql);
    }
}
echo "Company module boundary test passed\n";
