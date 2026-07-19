<?php
declare(strict_types=1);
namespace App\Services;
use App\Contracts\PasswordRecoveryMailerInterface;
use App\Shared\Domain\JobQueue;
use App\Shared\Infrastructure\Queue\PdoJobQueue;
use App\Shared\Infrastructure\Security\EncryptedPayload;
use PDO;
final class QueuedPasswordRecoveryMailer implements PasswordRecoveryMailerInterface
{
    public function __construct(private readonly JobQueue $jobs, private readonly EncryptedPayload $cipher) {}
    public static function fromConnection(PDO $connection): self
    {
        return new self(new PdoJobQueue($connection), EncryptedPayload::fromEnvironment());
    }
    public function sendOtp(string $email, string $otp, int $ttlMinutes): void
    {
        $this->enqueue('mail.password_otp', compact('email', 'otp', 'ttlMinutes'));
    }
    public function sendTemporaryPassword(string $email, string $temporaryPassword): void
    {
        $this->enqueue('mail.temporary_password', compact('email', 'temporaryPassword'));
    }
    public function sendReactivationPassword(string $email, string $temporaryPassword): void
    {
        $this->enqueue('mail.reactivation_password', compact('email', 'temporaryPassword'));
    }
    private function enqueue(string $type, array $payload): void
    {
        $this->jobs->enqueue($type, $this->cipher->encrypt($payload), 'mail', 5);
    }
}
