<?php
declare(strict_types=1);
namespace App\Shared\Infrastructure\Queue;
use App\Shared\Domain\JobQueue;
use PDO;
use Throwable;
final class PdoJobQueue implements JobQueue
{
    public function __construct(private readonly PDO $connection) {}
    public function enqueue(string $type, string $encryptedPayload, string $queue = 'default', int $maxAttempts = 5): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO background_jobs (queue_name, job_type, encrypted_payload, max_attempts)
             VALUES (:queue, :type, :payload, :max_attempts)'
        );
        $statement->execute(['queue' => $queue, 'type' => $type, 'payload' => $encryptedPayload,
            'max_attempts' => max(1, min($maxAttempts, 20))]);
        return (int) $this->connection->lastInsertId();
    }
    public function reserve(string $queue = 'default'): ?array
    {
        $this->connection->beginTransaction();
        try {
            $statement = $this->connection->prepare(
                'SELECT * FROM background_jobs WHERE queue_name = :queue AND status = "PENDING"
                 AND available_at <= UTC_TIMESTAMP(6) ORDER BY id LIMIT 1 FOR UPDATE SKIP LOCKED'
            );
            $statement->execute(['queue' => $queue]);
            $job = $statement->fetch(PDO::FETCH_ASSOC);
            if ($job === false) { $this->connection->commit(); return null; }
            $update = $this->connection->prepare(
                'UPDATE background_jobs SET status = "PROCESSING", attempts = attempts + 1,
                 reserved_at = UTC_TIMESTAMP(6) WHERE id = :id AND status = "PENDING"'
            );
            $update->execute(['id' => $job['id']]);
            $this->connection->commit();
            $job['attempts'] = (int) $job['attempts'] + 1;
            return $job;
        } catch (Throwable $throwable) {
            if ($this->connection->inTransaction()) $this->connection->rollBack();
            throw $throwable;
        }
    }
    public function complete(int $jobId): void
    {
        $statement = $this->connection->prepare(
            'UPDATE background_jobs SET status = "COMPLETED", completed_at = UTC_TIMESTAMP(6),
             encrypted_payload = "", last_error = NULL WHERE id = :id AND status = "PROCESSING"'
        );
        $statement->execute(['id' => $jobId]);
    }
    public function release(int $jobId, string $error, int $delaySeconds): void
    {
        $error = mb_substr(strip_tags($error), 0, 1000);
        $statement = $this->connection->prepare(
            'UPDATE background_jobs SET status = CASE WHEN attempts >= max_attempts THEN "FAILED" ELSE "PENDING" END,
             available_at = DATE_ADD(UTC_TIMESTAMP(6), INTERVAL :delay SECOND), reserved_at = NULL,
             last_error = :error WHERE id = :id AND status = "PROCESSING"'
        );
        $statement->bindValue(':delay', max(1, min($delaySeconds, 86400)), PDO::PARAM_INT);
        $statement->bindValue(':error', $error); $statement->bindValue(':id', $jobId, PDO::PARAM_INT);
        $statement->execute();
    }
}
