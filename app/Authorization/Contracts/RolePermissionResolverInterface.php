<?php

declare(strict_types=1);

namespace App\Authorization\Contracts;

use App\Authorization\DTO\ResolvedAuthorizationContext;

interface RolePermissionResolverInterface
{
    public function resolve(int $userId, ?int $companyId = null): ResolvedAuthorizationContext;
}
