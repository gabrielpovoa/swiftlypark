<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Core\View\BladeViewRenderer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

$root = dirname(__DIR__);
$views = [
    '403/403',
    '404/404',
    'About/about',
    'Audit/index',
    'Contact/contact',
    'Contact/ticket',
    'Finance/index',
    'Finance/print',
    'Home/home',
    'HomeDash/homedash',
    'Identity/index',
    'Login/RecoveryPassword',
    'Login/login',
    'Logs/print',
    'Profile/profile',
];

foreach ($views as $view) {
    $bladePath = $root . '/app/Views/' . $view . '.blade.php';
    $legacyPath = $root . '/app/Views/' . $view . '.php';

    if (!is_file($bladePath) || is_file($legacyPath)) {
        throw new RuntimeException("Migração Blade incompleta para {$view}.");
    }

    $source = file_get_contents($bladePath);
    if ($source === false || str_contains($source, '$this->partial(')) {
        throw new RuntimeException("A view {$view} ainda depende do contexto PHP legado.");
    }
}

$cache = sys_get_temp_dir() . '/swiftlypark-small-blade-test-' . bin2hex(random_bytes(6));
$renderer = new BladeViewRenderer($root . '/app/Views', $cache);
$compiler = new BladeCompiler(new Filesystem(), $cache, $root . '/app/Views');
$renderPartial = static fn (string $name, array $data = []): string =>
    sprintf('<!-- partial:%s -->', htmlspecialchars($name, ENT_QUOTES, 'UTF-8'));

try {
    foreach ($views as $view) {
        $sourcePath = $root . '/app/Views/' . $view . '.blade.php';
        $compiler->compile($sourcePath);
        $compiledPath = $compiler->getCompiledPath($sourcePath);
        $output = [];
        $exitCode = 0;
        exec(
            escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($compiledPath),
            $output,
            $exitCode
        );
        if ($exitCode !== 0) {
            throw new RuntimeException(
                "O Blade compilado de {$view} possui sintaxe inválida: " . implode(' ', $output)
            );
        }
    }

    $forbidden = '<script>alert(1)</script>';
    $forbiddenEscaped = '&lt;script&gt;alert(1)&lt;/script&gt;';
    $errorPage = $renderer->render('403/403', [
        'title' => 'Acesso negado',
        'message' => $forbidden,
        'renderPartial' => $renderPartial,
    ]);
    if (str_contains($errorPage, $forbidden)
        || !str_contains($errorPage, $forbiddenEscaped)
        || !str_contains($errorPage, 'partial:head')) {
        throw new RuntimeException('A página 403 não preservou partial ou escape do Blade.');
    }

    $layout = $renderer->render('Home/home', [
        'title' => 'Painel',
        'content' => '<section id="trusted-view">Conteúdo</section>',
        'renderPartial' => $renderPartial,
    ]);
    if (!str_contains($layout, '<section id="trusted-view">')
        || !str_contains($layout, 'partial:header')
        || !str_contains($layout, 'partial:footer')) {
        throw new RuntimeException('O layout Blade não compôs conteúdo e partials corretamente.');
    }
} finally {
    foreach (glob($cache . '/*') ?: [] as $compiled) {
        unlink($compiled);
    }
    rmdir($cache);
}

echo "Small view folders Blade test passed\n";
