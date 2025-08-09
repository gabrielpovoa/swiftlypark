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

    protected function setView($view, $data = [])
    {
        $viewFile = $this->viewsPath . str_replace(['.', '/'], DIRECTORY_SEPARATOR, $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new Exception("View '{$view}' não encontrada em {$viewFile}");
        }

        extract($data);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = $this->viewsPath . 'Home' . DIRECTORY_SEPARATOR . 'home.php';

        if (!file_exists($layoutFile)) {
            throw new Exception("Layout 'Home/home' não encontrado em {$layoutFile}");
        }

        require $layoutFile;
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
}
