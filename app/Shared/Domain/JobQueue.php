<?php
declare(strict_types=1);
namespace App\Shared\Domain;
interface JobQueue
{
    public function enqueue(string $type, string $encryptedPayload, string $queue = 'default', int $maxAttempts = 5): int;
    public function reserve(string $queue = 'default'): ?array;
    public function complete(int $jobId): void;
    public function release(int $jobId, string $error, int $delaySeconds): void;
}
