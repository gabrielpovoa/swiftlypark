<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class ForbiddenException extends RuntimeException
{
    public function __construct(
        private string $permission,
        string $message = 'Você não possui permissão para executar esta ação.'
    ) {
        parent::__construct($message);
    }

    public function permission(): string
    {
        return $this->permission;
    }
}
