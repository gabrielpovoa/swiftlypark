<?php

declare(strict_types=1);

namespace App\Middleware;

final class RegistrationBlockedMiddleware
{
    public function handle(): void
    {
        http_response_code(404);
    }
}
