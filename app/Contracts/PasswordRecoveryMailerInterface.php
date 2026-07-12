<?php

declare(strict_types=1);

namespace App\Contracts;

interface PasswordRecoveryMailerInterface
{
    public function sendOtp(string $email, string $otp, int $ttlMinutes): void;

    public function sendTemporaryPassword(string $email, string $temporaryPassword): void;

    public function sendReactivationPassword(string $email, string $temporaryPassword): void;
}
