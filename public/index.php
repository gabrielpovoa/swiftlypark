<?php
require __DIR__ . '/../vendor/autoload.php';

// Carregar rotas
$router = require __DIR__ . '/../routes/web.php';
$router->run();
