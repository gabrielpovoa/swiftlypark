<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure;

use PDO;

final class UserAccessRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function isActive(int $userId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1
             FROM usuario
             WHERE id_usuario = :user_id AND deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchColumn() !== false;
    }
}

