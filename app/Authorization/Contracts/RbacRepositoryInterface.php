<?php

declare(strict_types=1);

namespace App\Authorization\Contracts;

interface RbacRepositoryInterface
{
    public function findAuthorizationRowsForUser(int $userId): array;

    public function findDirectPermissionsForUser(int $userId): array;
}
