<?php
namespace Core;

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
        $path = $_GET['url'] ?? '/';

        var_dump($method, $path);
        exit;
    }

}
