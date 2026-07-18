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
                return;
            }

            foreach ($this->routes[$method] ?? [] as $route => $callback) {
                if (!str_contains($route, '{')) {
                    continue;
                }

                $patternSource = preg_replace_callback(
                    '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
                    static function (): string {
                        return '___ROUTE_INTEGER___';
                    },
                    $route
                );
                $pattern = str_replace(
                    '___ROUTE_INTEGER___',
                    '([0-9]+)',
                    preg_quote((string) $patternSource, '#')
                );

                if (preg_match('#^' . $pattern . '$#', $path, $matches) !== 1) {
                    continue;
                }

                array_shift($matches);
                call_user_func_array($callback, array_map('intval', $matches));
                return;
            }

            http_response_code(404);
            $controller = new Controller();
            $controller->render404();
        }
    }
