<?php

declare(strict_types=1);

namespace App\Repositories;

use DateTimeImmutable;
use PDO;

final class PasswordResetRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM password_resets WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $reset = $statement->fetch(PDO::FETCH_ASSOC);

        return $reset === false ? null : $reset;
    }

    public function findAuthorized(int $id): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM password_resets
             WHERE id = :id
               AND verified_at IS NOT NULL
               AND reset_token_hash IS NOT NULL
               AND reset_token_expires_at > CURRENT_TIMESTAMP
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $reset = $statement->fetch(PDO::FETCH_ASSOC);

        return $reset === false ? null : $reset;
    }

    public function saveOtp(
        string $email,
        string $otpHash,
        DateTimeImmutable $expiresAt,
        DateTimeImmutable $requestWindowStartedAt,
        int $requestCount
    ): int {
        $statement = $this->connection->prepare(
            'INSERT INTO password_resets (
                email, otp_hash, expires_at, attempts, request_count,
                request_window_started_at, created_at, verified_at,
                reset_token_hash, reset_token_expires_at
             ) VALUES (
                :email, :otp_hash, :expires_at, 0, :request_count,
                :request_window_started_at, CURRENT_TIMESTAMP, NULL, NULL, NULL
             )
             ON DUPLICATE KEY UPDATE
                otp_hash = VALUES(otp_hash),
                expires_at = VALUES(expires_at),
                attempts = 0,
                request_count = VALUES(request_count),
                request_window_started_at = VALUES(request_window_started_at),
                created_at = CURRENT_TIMESTAMP,
                verified_at = NULL,
                reset_token_hash = NULL,
                reset_token_expires_at = NULL,
                id = LAST_INSERT_ID(id)'
        );
        $statement->execute([
            'email' => $email,
            'otp_hash' => $otpHash,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'request_count' => $requestCount,
            'request_window_started_at' => $requestWindowStartedAt->format('Y-m-d H:i:s'),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function claimAttempt(int $id, int $maximumAttempts): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE password_resets
             SET attempts = attempts + 1
             WHERE id = :id
               AND attempts < :maximum_attempts
               AND expires_at > CURRENT_TIMESTAMP
               AND verified_at IS NULL'
        );
        $statement->execute([
            'id' => $id,
            'maximum_attempts' => $maximumAttempts,
        ]);

        return $statement->rowCount() === 1;
    }

    public function authorizeReset(
        int $id,
        string $tokenHash,
        DateTimeImmutable $expiresAt
    ): void {
        $statement = $this->connection->prepare(
            'UPDATE password_resets
             SET verified_at = CURRENT_TIMESTAMP,
                 reset_token_hash = :token_hash,
                 reset_token_expires_at = :expires_at
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function delete(int $id): void
    {
        $statement = $this->connection->prepare(
            'DELETE FROM password_resets WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }

    public function deleteExpired(): int
    {
        $statement = $this->connection->prepare(
            'DELETE FROM password_resets
             WHERE (verified_at IS NULL AND expires_at <= CURRENT_TIMESTAMP)
                OR (verified_at IS NOT NULL AND reset_token_expires_at <= CURRENT_TIMESTAMP)'
        );
        $statement->execute();

        return $statement->rowCount();
    }
}
