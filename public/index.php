<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';
loadEnv(__DIR__ . '/../.env');

// Carregar rotas
$router = require __DIR__ . '/../routes/web.php';
$router->run();
