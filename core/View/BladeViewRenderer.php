<?php

declare(strict_types=1);

namespace Core\View;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use RuntimeException;

final class BladeViewRenderer
{
    private Factory $views;

    public function __construct(string $viewsPath, string $cachePath)
    {
        if ((!is_dir($cachePath) && !mkdir($cachePath, 0775, true) && !is_dir($cachePath))
            || !is_writable($cachePath)) {
            throw new RuntimeException('O diretório de cache do Blade não está disponível.');
        }

        $files = new Filesystem();
        $container = new Container();
        $events = new Dispatcher($container);
        $compiler = new BladeCompiler($files, $cachePath, $viewsPath);
        $engines = new EngineResolver();
        $engines->register('blade', static fn (): CompilerEngine => new CompilerEngine(
            $compiler,
            $files
        ));

        $this->views = new Factory(
            $engines,
            new FileViewFinder($files, [$viewsPath], ['blade.php']),
            $events
        );
        $this->views->setContainer($container);
    }

    public function render(string $view, array $data = []): string
    {
        return $this->views
            ->make(str_replace('/', '.', $view), $data)
            ->render();
    }
}
