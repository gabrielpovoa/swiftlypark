<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PasswordRecoveryMailerInterface;
use App\Exceptions\InvalidOtpException;
use App\Exceptions\InvalidResetAuthorizationException;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Throwable;

final class OtpService
{
    private const OTP_TTL_MINUTES = 10;
    private const RESET_TTL_MINUTES = 10;
    private const MAX_OTP_ATTEMPTS = 3;
    private const MAX_REQUESTS_PER_WINDOW = 3;
    private const REQUEST_WINDOW_MINUTES = 15;
    private const MIN_RESEND_INTERVAL_SECONDS = 60;

    public function __construct(
        private PDO $connection,
        private PasswordResetRepository $passwordResets,
        private UserRepository $users,
        private PasswordRecoveryMailerInterface $mailer
    ) {
    }

    public function requestOtp(string $email): void
    {
        $email = $this->normalizeEmail($email);

        if (!$this->users->existsByEmail($email)) {
            return;
        }

        $now = $this->now();
        $existing = $this->passwordResets->findByEmail($email);
        [$windowStartedAt, $requestCount] = $this->requestWindow($existing, $now);

        if ($this->isRequestLimited($existing, $windowStartedAt, $requestCount, $now)) {
            return;
        }

        $otp = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);

        if ($otpHash === false) {
            throw new \RuntimeException('Não foi possível proteger o OTP.');
        }

        $this->passwordResets->saveOtp(
            $email,
            $otpHash,
            $now->modify('+' . self::OTP_TTL_MINUTES . ' minutes'),
            $windowStartedAt,
            $requestCount + 1
        );

        $this->mailer->sendOtp($email, $otp, self::OTP_TTL_MINUTES);
    }

    public function validateOtp(string $email, string $otp): array
    {
        $email = $this->normalizeEmail($email);
        $reset = $this->passwordResets->findByEmail($email);
        $now = $this->now();

        if (
            $reset === null
            || (int) $reset['attempts'] >= self::MAX_OTP_ATTEMPTS
            || $this->databaseDate($reset['expires_at']) <= $now
        ) {
            throw new InvalidOtpException('Código inválido ou expirado.');
        }

        if (
            !$this->passwordResets->claimAttempt(
                (int) $reset['id'],
                self::MAX_OTP_ATTEMPTS
            )
        ) {
            throw new InvalidOtpException('Código inválido ou expirado.');
        }

        if (!password_verify($otp, $reset['otp_hash'])) {
            throw new InvalidOtpException('Código inválido ou expirado.');
        }

        $token = bin2hex(random_bytes(32));
        $this->passwordResets->authorizeReset(
            (int) $reset['id'],
            hash('sha256', $token),
            $now->modify('+' . self::RESET_TTL_MINUTES . ' minutes')
        );

        return [
            'id' => (int) $reset['id'],
            'token' => $token,
            'email' => $email,
            'expires_at' => $now
                ->modify('+' . self::RESET_TTL_MINUTES . ' minutes')
                ->getTimestamp(),
        ];
    }

    public function resetPassword(int $resetId, string $token, string $password): void
    {
        $reset = $this->passwordResets->findAuthorized($resetId);

        if (
            $reset === null
            || !hash_equals($reset['reset_token_hash'], hash('sha256', $token))
        ) {
            throw new InvalidResetAuthorizationException(
                'A autorização para redefinir a senha é inválida ou expirou.'
            );
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            throw new \RuntimeException('Não foi possível proteger a nova senha.');
        }

        $this->connection->beginTransaction();

        try {
            if (!$this->users->updatePasswordByEmail($reset['email'], $passwordHash)) {
                throw new InvalidResetAuthorizationException(
                    'A conta vinculada à recuperação não está disponível.'
                );
            }

            $this->passwordResets->delete($resetId);
            $this->connection->commit();
        } catch (Throwable $throwable) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $throwable;
        }
    }

    public function purgeExpired(): int
    {
        return $this->passwordResets->deleteExpired();
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function requestWindow(?array $reset, DateTimeImmutable $now): array
    {
        if ($reset === null) {
            return [$now, 0];
        }

        $windowStartedAt = $this->databaseDate(
            $reset['request_window_started_at']
        );

        if ($windowStartedAt <= $now->modify('-' . self::REQUEST_WINDOW_MINUTES . ' minutes')) {
            return [$now, 0];
        }

        return [$windowStartedAt, (int) $reset['request_count']];
    }

    private function isRequestLimited(
        ?array $reset,
        DateTimeImmutable $windowStartedAt,
        int $requestCount,
        DateTimeImmutable $now
    ): bool {
        if ($requestCount >= self::MAX_REQUESTS_PER_WINDOW) {
            return true;
        }

        if ($reset === null || $windowStartedAt == $now) {
            return false;
        }

        $lastRequestedAt = $this->databaseDate($reset['created_at']);

        return $lastRequestedAt > $now->modify(
            '-' . self::MIN_RESEND_INTERVAL_SECONDS . ' seconds'
        );
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    private function databaseDate(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
