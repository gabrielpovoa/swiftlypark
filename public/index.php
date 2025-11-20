<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';
loadEnv(__DIR__ . '/../.env');
date_default_timezone_set("America/Sao_Paulo");

// Carregar rotas
$router = require __DIR__ . '/../routes/web.php';
$router->run();
