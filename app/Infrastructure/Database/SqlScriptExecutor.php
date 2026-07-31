<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;
use RuntimeException;

final class SqlScriptExecutor
{
    public function __construct(private PDO $connection)
    {
    }

    public function executeFile(string $path): int
    {
        $sql = file_get_contents($path);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('Script SQL vazio ou ilegível: ' . $path);
        }

        return $this->execute($sql);
    }

    public function execute(string $sql): int
    {
        $statements = $this->statements($sql);

        foreach ($statements as $statement) {
            if (preg_match(
                '/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN|WITH|EXECUTE)\b/i',
                $statement
            ) === 1) {
                $query = $this->connection->query($statement);
                if ($query !== false) {
                    $query->fetchAll();
                    $query->closeCursor();
                }
                continue;
            }

            $this->connection->exec($statement);
        }

        return count($statements);
    }

    /**
     * Divide scripts SQL sem quebrar ponto e vírgula dentro de strings.
     * Comentários de dump são descartados; diretivas DELIMITER não são aceitas.
     *
     * @return list<string>
     */
    public function statements(string $sql): array
    {
        if (preg_match('/^\s*DELIMITER\b/im', $sql) === 1) {
            throw new RuntimeException('Scripts com DELIMITER não são suportados.');
        }

        $statements = [];
        $buffer = '';
        $length = strlen($sql);
        $quote = null;
        $lineComment = false;
        $blockComment = false;

        for ($index = 0; $index < $length; $index++) {
            $character = $sql[$index];
            $next = $index + 1 < $length ? $sql[$index + 1] : '';

            if ($lineComment) {
                if ($character === "\n") {
                    $lineComment = false;
                    $buffer .= ' ';
                }
                continue;
            }

            if ($blockComment) {
                if ($character === '*' && $next === '/') {
                    $blockComment = false;
                    $index++;
                    $buffer .= ' ';
                }
                continue;
            }

            if ($quote !== null) {
                $buffer .= $character;

                if ($character === '\\' && $next !== '') {
                    $buffer .= $next;
                    $index++;
                    continue;
                }

                if ($character === $quote) {
                    if ($next === $quote) {
                        $buffer .= $next;
                        $index++;
                        continue;
                    }
                    $quote = null;
                }
                continue;
            }

            if ($character === '-' && $next === '-'
                && ($index + 2 >= $length || ctype_space($sql[$index + 2]))) {
                $lineComment = true;
                $index++;
                continue;
            }

            if ($character === '#') {
                $lineComment = true;
                continue;
            }

            if ($character === '/' && $next === '*') {
                $blockComment = true;
                $index++;
                continue;
            }

            if (in_array($character, ["'", '"', '`'], true)) {
                $quote = $character;
                $buffer .= $character;
                continue;
            }

            if ($character === ';') {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $character;
        }

        if ($quote !== null || $blockComment) {
            throw new RuntimeException('Script SQL contém string ou comentário não finalizado.');
        }

        $statement = trim($buffer);
        if ($statement !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }
}
