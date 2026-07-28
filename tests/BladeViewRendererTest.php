<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Core\View\BladeViewRenderer;

$cache = sys_get_temp_dir() . '/swiftlypark-blade-test-' . bin2hex(random_bytes(6));
$renderer = new BladeViewRenderer(__DIR__ . '/Fixtures/views', $cache);
$output = $renderer->render('escaped', [
    'title' => '<script>alert(1)</script>',
    'visible' => true,
    'items' => ['Carro', '<img src=x onerror=alert(1)>'],
]);

if (str_contains($output, '<script>') || str_contains($output, '<img src=x')) {
    throw new RuntimeException('Blade não escapou dados não confiáveis.');
}
if (!str_contains($output, '&lt;script&gt;alert(1)&lt;/script&gt;')
    || !str_contains($output, '<span>Carro</span>')) {
    throw new RuntimeException('Blade não renderizou condicionais e coleções corretamente.');
}

foreach (glob($cache . '/*') ?: [] as $compiled) {
    unlink($compiled);
}
rmdir($cache);

echo "Blade view renderer test passed\n";
