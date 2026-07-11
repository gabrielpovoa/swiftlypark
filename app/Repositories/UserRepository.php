<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function existsByEmail(string $email): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM usuario WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);

        return $statement->fetchColumn() !== false;
    }

    public function updatePasswordByEmail(string $email, string $passwordHash): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE usuario
             SET senha_hash = :password_hash,
                 password_reset_required = 0
             WHERE email = :email'
        );
        $statement->execute([
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        return $statement->rowCount() === 1;
    }
}
