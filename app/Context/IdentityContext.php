<?php

declare(strict_types=1);

namespace App\Context;

use App\Exceptions\UnauthorizedException;
use LogicException;

final class IdentityContext
{
    private static ?RequestIdentity $identity = null;

    private function __construct()
    {
    }

    public static function set(RequestIdentity $identity): void
    {
        if (self::$identity !== null) {
            throw new LogicException('A identidade da requisição já foi definida.');
        }

        self::$identity = $identity;
    }

    public static function current(): RequestIdentity
    {
        if (self::$identity === null) {
            throw new UnauthorizedException('Não existe identidade autenticada.');
        }

        return self::$identity;
    }

    public static function clear(): void
    {
        self::$identity = null;
    }
}
