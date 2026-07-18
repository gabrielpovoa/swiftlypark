<?php

declare(strict_types=1);

$root = dirname(__DIR__);
foreach (['Billing', 'Parking', 'Finance', 'Companies'] as $module) {
    foreach (glob($root . '/app/' . $module . '/Application/*.php') ?: [] as $file) {
        $source = (string) file_get_contents($file);
        if (str_contains($source, 'use App\\' . $module . '\\Infrastructure\\')) {
            throw new RuntimeException('Application depende de Infrastructure: ' . $file);
        }
    }
}
foreach (glob($root . '/app/*/Domain/*Repository.php') ?: [] as $file) {
    $source = (string) file_get_contents($file);
    if (str_contains($source, 'PDO')) {
        throw new RuntimeException('Contrato de repository importa PDO: ' . $file);
    }
}
echo "Repository dependency boundary test passed\n";
