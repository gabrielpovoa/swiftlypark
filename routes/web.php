<?php
use Core\Router;
use App\Controllers\HomeController;
use App\Controllers\LoginController;
use App\Controllers\VacancyController;
use App\Controllers\LogsController;
use App\Controllers\ContactController;
use App\Controllers\AboutController;
use App\Controllers\CreateAccController;
use App\Controllers\ProfileController;

$router = new Router();

$router->get('', function() {
    $controller = new HomeController();
    $controller->index();
});

$router->get('login', function() {
    $controller = new LoginController();
    $controller->index();
});

$router->get('login/authenticate', function() {
    $controller = new LoginController();
    $controller->authenticate();
});

$router->get('CreateAcc', function () {
  $controller = new CreateAccController();
  $controller->index();
});

$router->get('Profile', function () {
    $controller = new ProfileController();
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

$router->get('vacancy/manage', function() {
    $controller = new VacancyController();
    $controller->manage();
});

$router->get('logs/options', function () {
    $controller = new LogsController();
    $controller->options();
});

$router->get('logs/print', function () {
    $controller = new LogsController();
    $controller->print();
});

$router->get('Contact', function () {
    $controller = new ContactController();
    $controller->index();
});

$router->post('Contact/SendSMTP', function () {
    $controller = new ContactController();
    $controller->SendSMTP();
});

$router->get('About', function () {
    $controller = new AboutController();
    $controller->index();
});

return $router;
