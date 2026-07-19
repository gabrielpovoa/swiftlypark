<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CheckoutReceiptMailerInterface;
use App\Shared\Domain\JobQueue;
use App\Shared\Infrastructure\Queue\PdoJobQueue;
use App\Shared\Infrastructure\Security\EncryptedPayload;
use PDO;

final class QueuedCheckoutReceiptMailer implements CheckoutReceiptMailerInterface
{
    public function __construct(
        private readonly JobQueue $jobs,
        private readonly EncryptedPayload $cipher
    ) {
    }

    public static function fromConnection(PDO $connection): self
    {
        return new self(
            new PdoJobQueue($connection),
            EncryptedPayload::fromEnvironment()
        );
    }

    public function sendReceipt(array $receipt): void
    {
        $this->jobs->enqueue(
            'mail.checkout_receipt',
            $this->cipher->encrypt($receipt),
            'mail',
            5
        );
    }
}
