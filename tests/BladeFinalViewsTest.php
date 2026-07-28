<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Core\Controller;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

$root = dirname(__DIR__);
$legacyViews = glob($root . '/app/Views/**/*.php') ?: [];
$legacyViews = array_values(array_filter(
    $legacyViews,
    static fn (string $path): bool => !str_ends_with($path, '.blade.php')
));

if ($legacyViews !== []) {
    throw new RuntimeException(
        'Ainda existem views PHP legadas: ' . implode(', ', $legacyViews)
    );
}

$bladeViews = glob($root . '/app/Views/**/*.blade.php') ?: [];
if ($bladeViews === []) {
    throw new RuntimeException('Nenhuma view Blade foi localizada.');
}

$cache = sys_get_temp_dir() . '/swiftlypark-final-blade-test-' . bin2hex(random_bytes(6));
mkdir($cache, 0775, true);
$compiler = new BladeCompiler(new Filesystem(), $cache, $root . '/app/Views');

try {
    foreach ($bladeViews as $viewPath) {
        $compiler->compile($viewPath);
        $compiledPath = $compiler->getCompiledPath($viewPath);
        $output = [];
        $exitCode = 0;
        exec(
            escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($compiledPath),
            $output,
            $exitCode
        );
        if ($exitCode !== 0) {
            throw new RuntimeException(
                'Blade inválido em ' . $viewPath . ': ' . implode(' ', $output)
            );
        }
    }
} finally {
    foreach (glob($cache . '/*') ?: [] as $compiled) {
        unlink($compiled);
    }
    rmdir($cache);
}

$controller = new class () extends Controller {
    public function template(string $view, array $data = []): string
    {
        return $this->renderTemplate($view, $data);
    }
};

$unsafe = '<script>alert(1)</script>';
$head = $controller->template('partials/head', ['title' => $unsafe]);
$badge = $controller->template('partials/role-badge', [
    'roleMetadata' => ['label' => $unsafe, 'icon' => 'shield'],
]);

if (str_contains($head, $unsafe)
    || str_contains($badge, $unsafe)
    || !str_contains($head, '&lt;script&gt;alert(1)&lt;/script&gt;')
    || !str_contains($badge, '&lt;script&gt;alert(1)&lt;/script&gt;')) {
    throw new RuntimeException('Os partials Blade não escaparam conteúdo externo.');
}

echo "Final Blade views test passed\n";
