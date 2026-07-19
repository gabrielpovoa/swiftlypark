<?php

declare(strict_types=1);

namespace App\Contracts;

interface CheckoutReceiptMailerInterface
{
    /** @param array<string, mixed> $receipt */
    public function sendReceipt(array $receipt): void;
}
