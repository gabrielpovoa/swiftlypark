<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

final class PasswordGeneratorService
{
    private const ALPHANUMERIC = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';

    public function temporary(int $length = 12): string
    {
        if ($length < 8) {
            throw new InvalidArgumentException('A senha temporária deve ter ao menos 8 caracteres.');
        }

        $password = '';
        $max = strlen(self::ALPHANUMERIC) - 1;

        for ($index = 0; $index < $length; $index++) {
            $password .= self::ALPHANUMERIC[random_int(0, $max)];
        }

        return $password;
    }
}
