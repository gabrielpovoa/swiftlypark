<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Context\IdentityContext;
use App\Context\RequestIdentity;
use App\Exceptions\UnauthorizedException;
use DateTimeImmutable;
use DateTimeZone;

final class IdentityMiddleware
{
    public function handle(callable $next): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = filter_var(
            $_SESSION['user_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($userId === false) {
            throw new UnauthorizedException('A autenticação é obrigatória.');
        }

        $email = filter_var(
            $_SESSION['user_email'] ?? null,
            FILTER_VALIDATE_EMAIL
        );

        if ($email === false) {
            throw new UnauthorizedException('A identidade da sessão é inválida.');
        }

        $identity = new RequestIdentity(
            $userId,
            strtolower($email),
            $this->resolveIpAddress(),
            $this->uuid(),
            new DateTimeImmutable('now', new DateTimeZone('UTC')),
            $this->permissions()
        );

        IdentityContext::set($identity);

        try {
            $next();
        } finally {
            IdentityContext::clear();
        }
    }

    private function resolveIpAddress(): string
    {
        $candidate = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        return filter_var($candidate, FILTER_VALIDATE_IP) !== false
            ? $candidate
            : '0.0.0.0';
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20)
        );
    }

    private function permissions(): array
    {
        $permissions = $_SESSION['permissions'] ?? [];

        if (!is_array($permissions)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            $permissions,
            static fn (mixed $permission): bool => is_string($permission)
                && preg_match('/^[a-z][a-z0-9._-]+$/', $permission) === 1
        )));
    }
}
