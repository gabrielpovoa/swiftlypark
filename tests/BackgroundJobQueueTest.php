<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Contracts\PasswordRecoveryMailerInterface;
use App\Shared\Application\JobWorker;
use App\Shared\Domain\JobQueue;
use App\Shared\Infrastructure\Security\EncryptedPayload;
use App\Services\QueuedPasswordRecoveryMailer;

final class MemoryJobQueue implements JobQueue
{
    public array $jobs = [];
    public function enqueue(string $type, string $encryptedPayload, string $queue = 'default', int $maxAttempts = 5): int
    {
        $id = count($this->jobs) + 1;
        $this->jobs[$id] = ['id' => $id, 'job_type' => $type, 'encrypted_payload' => $encryptedPayload,
            'attempts' => 0, 'max_attempts' => $maxAttempts, 'status' => 'PENDING'];
        return $id;
    }
    public function reserve(string $queue = 'default'): ?array
    {
        foreach ($this->jobs as &$job) if ($job['status'] === 'PENDING') {
            $job['status'] = 'PROCESSING'; $job['attempts']++; return $job;
        }
        return null;
    }
    public function complete(int $jobId): void { $this->jobs[$jobId]['status'] = 'COMPLETED'; }
    public function release(int $jobId, string $error, int $delaySeconds): void
    {
        $this->jobs[$jobId]['status'] = $this->jobs[$jobId]['attempts'] >= $this->jobs[$jobId]['max_attempts']
            ? 'FAILED' : 'PENDING';
    }
}
final class CapturingQueueMailer implements PasswordRecoveryMailerInterface
{
    public array $messages = [];
    public function sendOtp(string $email, string $otp, int $ttlMinutes): void { $this->messages[] = compact('email', 'otp'); }
    public function sendTemporaryPassword(string $email, string $temporaryPassword): void { $this->messages[] = compact('email', 'temporaryPassword'); }
    public function sendReactivationPassword(string $email, string $temporaryPassword): void { $this->messages[] = compact('email', 'temporaryPassword'); }
}

$cipher = new EncryptedPayload('test-job-queue-key-with-32-characters');
$encrypted = $cipher->encrypt(['email' => 'user@example.test', 'temporaryPassword' => 'Secret123']);
if (str_contains($encrypted, 'Secret123') || $cipher->decrypt($encrypted)['temporaryPassword'] !== 'Secret123') {
    throw new RuntimeException('Payload não foi cifrado/decriptado corretamente.');
}
$tamperRejected = false;
try {
    $tampered = substr($encrypted, 0, -2) . 'AA';
    $cipher->decrypt($tampered);
} catch (RuntimeException) {
    $tamperRejected = true;
}
if (!$tamperRejected) {
    throw new RuntimeException('Payload adulterado foi aceito.');
}
$queue = new MemoryJobQueue(); $mailer = new CapturingQueueMailer();
(new QueuedPasswordRecoveryMailer($queue, $cipher))->sendTemporaryPassword(
    'user@example.test',
    'Secret123'
);
(new JobWorker($queue, $cipher, $mailer))->runOnce('mail');
if ($queue->jobs[1]['status'] !== 'COMPLETED' || $mailer->messages[0]['temporaryPassword'] !== 'Secret123') {
    throw new RuntimeException('Worker não concluiu o job de e-mail.');
}
echo "Background job queue test passed\n";
