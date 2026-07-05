<?php

declare(strict_types=1);

namespace App\Authorization\Repositories;

use App\Authorization\Contracts\RbacRepositoryInterface;
use PDO;

final class RbacRepository implements RbacRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function findAuthorizationRowsForUser(int $userId): array
    {
        $statement = $this->connection->prepare(
            'SELECT
                r.slug AS role_slug,
                r.label AS role_label,
                r.icon_slug,
                r.display_priority,
                p.slug AS permission_slug
             FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id AND r.is_active = 1
             LEFT JOIN role_permissions rp ON rp.role_id = r.id
             LEFT JOIN permissions p
                ON p.id = rp.permission_id AND p.is_active = 1
             WHERE ur.user_id = :user_id
             ORDER BY r.display_priority ASC, r.slug ASC, p.slug ASC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
