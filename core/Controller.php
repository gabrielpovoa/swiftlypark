<?php
    namespace Core;

    use Exception;
    use Core\View\BladeViewRenderer;

    class Controller
    {
        private $viewsPath;
        private BladeViewRenderer $blade;

        public function __construct()
        {
            $this->viewsPath = dirname(__DIR__) . '/app/Views/';
            $this->blade = new BladeViewRenderer(
                $this->viewsPath,
                dirname(__DIR__) . '/storage/framework/views'
            );
        }

        protected function setView($view, $data = [], $useLayout = true)
        {
            $content = $this->renderTemplate((string) $view, $data);

            if ($useLayout) {
                $bladeLayout = $this->viewsPath . 'Home' . DIRECTORY_SEPARATOR . 'home.blade.php';
                $layoutFile = $this->viewsPath . 'Home' . DIRECTORY_SEPARATOR . 'home.php';

                if (file_exists($bladeLayout)) {
                    echo $this->blade->render('Home/home', $this->bladeData(
                        array_merge($data, ['content' => $content])
                    ));
                    return;
                }

                if (!file_exists($layoutFile)) {
                    throw new Exception("Layout 'Home/home' não encontrado em {$layoutFile}");
                }

                extract($data);
                require $layoutFile;
            } else {
                // Exibe só o conteúdo da view (sem layout)
                echo $content;
            }
        }

        /**
         * Inclui um partial (header, footer, etc.)
         */
        protected function partial($partial, $data = [])
        {
            echo $this->renderPartial((string) $partial, $data);
        }

        private function bladeData(array $data): array
        {
            $data['renderPartial'] = fn (string $partial, array $partialData = []): string =>
                $this->renderPartial($partial, $partialData);

            return $data;
        }

        private function renderPartial(string $partial, array $data = []): string
        {
            $bladePartial = $this->viewsPath
                . 'partials'
                . DIRECTORY_SEPARATOR
                . $partial
                . '.blade.php';
            $partialFile = $this->viewsPath . 'partials' . DIRECTORY_SEPARATOR . $partial . '.php';

            if (file_exists($bladePartial)) {
                return $this->blade->render(
                    'partials/' . $partial,
                    $this->bladeData($data)
                );
            }

            if (!file_exists($partialFile)) {
                throw new Exception("Partial '{$partial}' não encontrada em {$partialFile}");
            }

            extract($data);
            ob_start();
            require $partialFile;

            return (string) ob_get_clean();
        }

        protected function renderTemplate(string $view, array $data = []): string
        {
            $relativeView = str_replace(['.', '/'], DIRECTORY_SEPARATOR, $view);
            $bladeFile = $this->viewsPath . $relativeView . '.blade.php';
            $viewFile = $this->viewsPath . $relativeView . '.php';

            if (file_exists($bladeFile)) {
                return $this->blade->render($view, $this->bladeData($data));
            }

            if (!file_exists($viewFile)) {
                throw new Exception("View '{$view}' não encontrada.");
            }

            extract($data);
            ob_start();
            require $viewFile;

            return (string) ob_get_clean();
        }

        protected function includeView($view, $data = [])
        {
            echo $this->renderTemplate((string) $view, $data);
        }

        public function render404()
        {
            http_response_code(404);
            $this->setView('404/404', ['title' => 'Página Não Encontrada'], false); // false para não usar layout
            exit;
        }

        public function render403(): void
        {
            http_response_code(403);
            $this->setView('403/403', [
                'title' => 'Acesso Negado - SwiftlyPark',
                'message' => 'Você não possui permissão para acessar este recurso.',
            ], false);
            exit;
        }
    }
