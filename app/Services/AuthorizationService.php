<?php

declare(strict_types=1);

namespace App\Services;

use App\Context\RequestIdentity;
use App\Exceptions\ForbiddenException;
use InvalidArgumentException;

final class AuthorizationService
{
    public function __construct(private RequestIdentity $identity)
    {
    }

    public function can(string $permission): bool
    {
        $this->assertValidPermission($permission);

        if ($permission === 'identity.manage'
            && $this->hasAnyRole(['master', 'super-admin', 'admin'])) {
            return true;
        }

        return in_array(
            $permission,
            $this->identity->permissions(),
            true
        );
    }

    public function hasAnyRole(array $roles): bool
    {
        return array_intersect($roles, $this->identity->roleSlugs()) !== [];
    }

    public function check(string $permission): void
    {
        if (!$this->can($permission)) {
            throw new ForbiddenException($permission);
        }
    }

    private function assertValidPermission(string $permission): void
    {
        if (preg_match('/^[a-z][a-z0-9._-]+$/', $permission) !== 1) {
            throw new InvalidArgumentException(
                'O identificador da permissão é inválido.'
            );
        }
    }
}
