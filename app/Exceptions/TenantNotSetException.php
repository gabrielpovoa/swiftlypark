<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class TenantNotSetException extends RuntimeException
{
    public function __construct(string $message = 'O contexto do tenant não foi inicializado.')
    {
        parent::__construct($message);
    }
}
