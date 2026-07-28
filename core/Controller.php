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
            $relativeView = str_replace(['.', '/'], DIRECTORY_SEPARATOR, $view);
            $bladeFile = $this->viewsPath . $relativeView . '.blade.php';
            $viewFile = $this->viewsPath . $relativeView . '.php';

            if (file_exists($bladeFile)) {
                $content = $this->blade->render((string) $view, $data);
                extract($data);
            } else {
                if (!file_exists($viewFile)) {
                    throw new Exception("View '{$view}' não encontrada.");
                }
                extract($data);
                ob_start();
                require $viewFile;
                $content = ob_get_clean();
            }

            if ($useLayout) {
                $layoutFile = $this->viewsPath . 'Home' . DIRECTORY_SEPARATOR . 'home.php';

                if (!file_exists($layoutFile)) {
                    throw new Exception("Layout 'Home/home' não encontrado em {$layoutFile}");
                }

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
            $partialFile = $this->viewsPath . 'partials' . DIRECTORY_SEPARATOR . $partial . '.php';

            if (!file_exists($partialFile)) {
                throw new Exception("Partial '{$partial}' não encontrada em {$partialFile}");
            }

            extract($data);
            require $partialFile;
        }

        protected function includeView($view, $data = [])
        {
            $viewFile = $this->viewsPath . str_replace(['.', '/'], DIRECTORY_SEPARATOR, $view) . '.php';

            if (!file_exists($viewFile)) {
                throw new \Exception("View '{$view}' não encontrada em {$viewFile}");
            }

            extract($data);
            include $viewFile;
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
