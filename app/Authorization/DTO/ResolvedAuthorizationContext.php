<?php

declare(strict_types=1);

namespace App\Authorization\DTO;

final class ResolvedAuthorizationContext
{
    public function __construct(
        private array $roleSlugs,
        private array $permissions,
        private RoleMetadata $roleMetadata
    ) {
    }

    public function roleSlugs(): array
    {
        return $this->roleSlugs;
    }

    public function permissions(): array
    {
        return $this->permissions;
    }

    public function roleMetadata(): RoleMetadata
    {
        return $this->roleMetadata;
    }
}
