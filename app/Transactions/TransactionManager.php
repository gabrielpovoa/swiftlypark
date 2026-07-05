<?php

declare(strict_types=1);

namespace App\Transactions;

use PDO;
use Throwable;

final class TransactionManager
{
    public function __construct(private PDO $connection)
    {
    }

    public function run(callable $operation): mixed
    {
        $this->connection->beginTransaction();

        try {
            $result = $operation();
            $this->connection->commit();

            return $result;
        } catch (Throwable $throwable) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $throwable;
        }
    }
}
