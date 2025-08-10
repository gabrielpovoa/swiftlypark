<?php
    namespace Core;

    use Exception;

    class Controller
    {
        private $viewsPath;

        public function __construct()
        {
            $this->viewsPath = dirname(__DIR__) . '/app/views/';
        }

        protected function setView($view, $data = [], $useLayout = true)
        {
            $viewFile = $this->viewsPath . str_replace(['.', '/'], DIRECTORY_SEPARATOR, $view) . '.php';

            if (!file_exists($viewFile)) {
                throw new Exception("View '{$view}' não encontrada em {$viewFile}");
            }

            extract($data);

            ob_start();
            require $viewFile;
            $content = ob_get_clean();

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
    }
