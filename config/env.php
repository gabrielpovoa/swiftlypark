<?php
function loadEnv($path)
{
    if (!file_exists($path)) {
        throw new Exception(".env file not found at: " . $path);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignora comentários
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        // Divide chave e valor
        [$name, $value] = explode('=', $line, 2);

        // Remove aspas se existirem
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        // Define variável de ambiente
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
