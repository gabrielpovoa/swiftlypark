<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use DateTimeImmutable;
use DomainException;

final class DatePeriod
{
    public function __construct(
        private readonly DateTimeImmutable $startInclusive,
        private readonly DateTimeImmutable $endExclusive
    ) {
        if ($endExclusive <= $startInclusive) {
            throw new DomainException('O fim do período deve ser posterior ao início.');
        }
    }
    public function start(): DateTimeImmutable { return $this->startInclusive; }
    public function end(): DateTimeImmutable { return $this->endExclusive; }
    public function contains(DateTimeImmutable $instant): bool
    {
        return $instant >= $this->startInclusive && $instant < $this->endExclusive;
    }
}
