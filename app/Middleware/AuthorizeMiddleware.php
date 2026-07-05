<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\ForbiddenException;
use App\Services\AuthorizationService;
use App\Services\SecurityAuditService;

final class AuthorizeMiddleware
{
    public function __construct(
        private AuthorizationService $authorization,
        private SecurityAuditService $securityAudit
    ) {
    }

    public function handle(
        string $permission,
        string $route,
        callable $next
    ): void {
        try {
            $this->authorization->check($permission);
            $next();
        } catch (ForbiddenException $exception) {
            $this->securityAudit->recordDeniedAccess(
                $exception->permission(),
                $route,
                'Usuário sem a permissão exigida.'
            );

            throw $exception;
        }
    }
}
