<?php

declare(strict_types=1);

namespace App\Identity\Domain\Events;

final class UserReactivatedEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly int $reactivatedBy
    ) {
    }
}

