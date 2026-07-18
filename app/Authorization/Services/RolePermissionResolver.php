<?php

declare(strict_types=1);

namespace App\Authorization\Services;

use App\Authorization\Contracts\RbacRepositoryInterface;
use App\Authorization\Contracts\RolePermissionResolverInterface;
use App\Authorization\DTO\ResolvedAuthorizationContext;
use App\Authorization\DTO\RoleMetadata;

final class RolePermissionResolver implements RolePermissionResolverInterface
{
    public function __construct(private RbacRepositoryInterface $repository)
    {
    }

    public function resolve(int $userId, ?int $companyId = null): ResolvedAuthorizationContext
    {
        $rows = $this->repository->findAuthorizationRowsForUser($userId, $companyId);
        $directPermissions = $this->repository->findDirectPermissionsForUser($userId);

        return $this->contextFromRows($rows, $directPermissions);
    }

    public function resolveSimulatedRole(
        string $roleSlug,
        array $extraPermissionIds = []
    ): ResolvedAuthorizationContext {
        $rows = $this->repository->findAuthorizationRowsForRole($roleSlug);
        $extraPermissions = $this->repository->findPermissionSlugsByIds($extraPermissionIds);

        return $this->contextFromRows($rows, $extraPermissions);
    }

    private function contextFromRows(array $rows, array $extraPermissions = []): ResolvedAuthorizationContext
    {
        $roles = [];
        $permissions = [];
        $metadata = null;

        foreach ($rows as $row) {
            $roles[] = $row['role_slug'];

            if ($metadata === null) {
                $metadata = new RoleMetadata(
                    $row['role_slug'],
                    $row['role_label'],
                    $row['icon_slug']
                );
            }

            if ($row['permission_slug'] !== null) {
                $permissions[] = $row['permission_slug'];
            }
        }
        $permissions = array_merge($permissions, $extraPermissions);

        return new ResolvedAuthorizationContext(
            array_values(array_unique($roles)),
            array_values(array_unique($permissions)),
            $metadata ?? new RoleMetadata('none', 'Sem papel', 'user')
        );
    }
}
