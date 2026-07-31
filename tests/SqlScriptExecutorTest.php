<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Database\SqlScriptExecutor;

$connection = new PDO('sqlite::memory:');
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$executor = new SqlScriptExecutor($connection);

$sql = <<<'SQL'
-- comentário inicial
CREATE TABLE example (id INTEGER PRIMARY KEY, value TEXT);
INSERT INTO example (id, value) VALUES (1, 'texto; preservado');
/* comentário entre comandos */
SELECT value FROM example WHERE value = 'texto; preservado';
SQL;

$statements = $executor->statements($sql);
if (count($statements) !== 3 || !str_contains($statements[1], 'texto; preservado')) {
    throw new RuntimeException('O parser SQL dividiu incorretamente strings ou comentários.');
}

if ($executor->execute($sql) !== 3) {
    throw new RuntimeException('A quantidade de comandos SQL executados divergiu.');
}

$value = $connection->query('SELECT value FROM example WHERE id = 1')->fetchColumn();
if ($value !== 'texto; preservado') {
    throw new RuntimeException('O executor SQL não persistiu o valor esperado.');
}

try {
    $executor->statements("DELIMITER //\nSELECT 1//");
    throw new RuntimeException('Script com DELIMITER foi aceito indevidamente.');
} catch (RuntimeException $exception) {
    if (!str_contains($exception->getMessage(), 'DELIMITER')) {
        throw $exception;
    }
}

echo "SQL script executor test passed\n";
