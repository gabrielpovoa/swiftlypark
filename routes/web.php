<?php
use Core\Router;
use App\Controllers\HomeController;

$router = new Router();

$router->get('/', function() {
    $controller = new HomeController();
    $controller->index();
});

return $router;
