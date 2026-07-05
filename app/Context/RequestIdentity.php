<?php

declare(strict_types=1);

namespace App\Context;

use DateTimeImmutable;

final class RequestIdentity
{
    public function __construct(
        private int $userId,
        private string $email,
        private string $ipAddress,
        private string $requestId,
        private DateTimeImmutable $requestedAt
    ) {
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function ipAddress(): string
    {
        return $this->ipAddress;
    }

    public function requestId(): string
    {
        return $this->requestId;
    }

    public function requestedAt(): DateTimeImmutable
    {
        return $this->requestedAt;
    }
}
