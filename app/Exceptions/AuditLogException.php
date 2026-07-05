<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class AuditLogException extends RuntimeException
{
    public function __construct(
        string $message,
        private string $requestId,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function requestId(): string
    {
        return $this->requestId;
    }
}
