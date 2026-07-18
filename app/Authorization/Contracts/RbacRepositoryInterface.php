<?php

declare(strict_types=1);

namespace App\Authorization\Contracts;

interface RbacRepositoryInterface
{
    public function findAuthorizationRowsForUser(int $userId, ?int $companyId = null): array;

    public function findDirectPermissionsForUser(int $userId): array;

    public function findAuthorizationRowsForRole(string $roleSlug): array;

    public function findPermissionSlugsByIds(array $permissionIds): array;
}
