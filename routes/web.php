<?php
use Core\Router;
use App\Controllers\HomeController;
use App\Controllers\LoginController;


$router = new Router();

$router->get('/', function() {
    $controller = new HomeController();
    $controller->index();
});

$router->get('/login', function() {
    $controller = new LoginController();
    $controller->index();
});


return $router;
