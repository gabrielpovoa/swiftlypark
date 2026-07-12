<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class SecurityCriticalException extends RuntimeException
{
    public const CODE_TENANT_CONTEXT_MISSING = 'SEC-TENANT-001';
    public const CODE_QUERY_WITHOUT_TENANT_SCOPE = 'SEC-TENANT-002';
    public const CODE_QUERY_TENANT_PARAMETER_MISSING = 'SEC-TENANT-003';
    public const CODE_CROSS_TENANT_ACCESS = 'SEC-TENANT-004';

    public function __construct(
        private string $securityCode,
        string $message,
        ?\Throwable $previous = null
    ) {
        parent::__construct('[' . $securityCode . '] ' . $message, 0, $previous);
    }

    public function securityCode(): string
    {
        return $this->securityCode;
    }
}
