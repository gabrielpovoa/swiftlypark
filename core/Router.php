<?php
    namespace Core;

    use Core\Controller;

    class Router {
        private $routes = [];

        public function get($path, $callback) {
            $this->routes['GET'][$path] = $callback;
        }

        public function post($path, $callback) {
            $this->routes['POST'][$path] = $callback;
        }

        public function run()
        {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = $_GET['url'] ?? '';

            // Remove barras extras no começo/fim
            $path = trim($path, '/');

            if (isset($this->routes[$method][$path])) {
                call_user_func($this->routes[$method][$path]);
            } else {
                // Resposta 404 com página personalizada
                http_response_code(404);
                $controller = new Controller();
                $controller->render404();
            }
        }
    }
