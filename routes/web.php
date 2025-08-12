<?php
use Core\Router;
use App\Controllers\HomeController;
use App\Controllers\LoginController;
use App\Controllers\VacancyController;
use App\controllers\LogsController;

$router = new Router();

$router->get('', function() {
    $controller = new HomeController();
    $controller->index();
});

$router->get('login', function() {
    $controller = new LoginController();
    $controller->index();
});

$router->get('vacancy', function() {
    $controller = new VacancyController();
    $controller->index();
});

// Rota GET para mostrar o formulário de candidatura
$router->get('vacancy/apply', function() {
    $controller = new VacancyController();
    $controller->apply();
});

// Rota POST para processar o envio do formulário
$router->post('vacancy/apply', function() {
    $controller = new VacancyController();
    $controller->apply();
});

$router->get('logs/options', function () {
    $controller = new LogsController();
    $controller->options();
});

$router->get('logs/print', function () {
    $controller = new LogsController();
    $controller->print();
});


return $router;
