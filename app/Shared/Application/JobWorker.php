<?php
declare(strict_types=1);
namespace App\Shared\Application;
use App\Contracts\PasswordRecoveryMailerInterface;
use App\Contracts\CheckoutReceiptMailerInterface;
use App\Shared\Domain\JobQueue;
use App\Shared\Infrastructure\Security\EncryptedPayload;
use RuntimeException;
use Throwable;
final class JobWorker
{
    public function __construct(
        private readonly JobQueue $jobs,
        private readonly EncryptedPayload $cipher,
        private readonly PasswordRecoveryMailerInterface $mailer,
        private readonly CheckoutReceiptMailerInterface $receiptMailer
    ) {}
    public function runOnce(string $queue = 'mail'): bool
    {
        $job = $this->jobs->reserve($queue);
        if ($job === null) return false;
        try {
            $payload = $this->cipher->decrypt((string) $job['encrypted_payload']);
            match ((string) $job['job_type']) {
                'mail.password_otp' => $this->mailer->sendOtp((string) $payload['email'],
                    (string) $payload['otp'], (int) $payload['ttlMinutes']),
                'mail.temporary_password' => $this->mailer->sendTemporaryPassword(
                    (string) $payload['email'], (string) $payload['temporaryPassword']),
                'mail.reactivation_password' => $this->mailer->sendReactivationPassword(
                    (string) $payload['email'], (string) $payload['temporaryPassword']),
                'mail.checkout_receipt' => $this->receiptMailer->sendReceipt($payload),
                default => throw new RuntimeException('Tipo de job não suportado.'),
            };
            $this->jobs->complete((int) $job['id']);
            return true;
        } catch (Throwable $throwable) {
            $attempt = (int) ($job['attempts'] ?? 1);
            $this->jobs->release((int) $job['id'], $throwable->getMessage(), min(3600, 2 ** $attempt));
            return true;
        }
    }
}
