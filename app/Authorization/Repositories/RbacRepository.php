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

    public function findAuthorizationRowsForUser(int $userId, ?int $companyId = null): array
    {
        if ($companyId !== null) {
            return $this->findAuthorizationRowsForUserAndCompany($userId, $companyId);
        }

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

    private function findAuthorizationRowsForUserAndCompany(int $userId, int $companyId): array
    {
        $statement = $this->connection->prepare(
            'SELECT
                r.slug AS role_slug,
                r.label AS role_label,
                r.icon_slug,
                r.display_priority,
                p.slug AS permission_slug
             FROM company_user cu
             INNER JOIN roles r ON r.id = cu.role_id AND r.is_active = 1
             LEFT JOIN role_permissions rp ON rp.role_id = r.id
             LEFT JOIN permissions p
                ON p.id = rp.permission_id AND p.is_active = 1
             WHERE cu.user_id = :tenant_user_id
               AND cu.company_id = :company_id
               AND cu.role_id IS NOT NULL

             UNION

             SELECT
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
             WHERE ur.user_id = :global_user_id
               AND r.slug IN (\'master\', \'super-admin\')
             ORDER BY display_priority ASC, role_slug ASC, permission_slug ASC'
        );
        $statement->execute([
            'tenant_user_id' => $userId,
            'company_id' => $companyId,
            'global_user_id' => $userId,
        ]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $rows !== [] ? $rows : $this->findAuthorizationRowsForUser($userId);
    }

    public function findDirectPermissionsForUser(int $userId): array
    {
        $statement = $this->connection->prepare(
            'SELECT p.slug
             FROM user_permissions up
             INNER JOIN permissions p
                ON p.id = up.permission_id AND p.is_active = 1
             WHERE up.user_id = :user_id
             ORDER BY p.slug'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public function findPermissionLabels(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $statement = $this->connection->prepare(
            'SELECT slug, name
             FROM permissions
             WHERE is_active = 1 AND slug IN (' . $placeholders . ')
             ORDER BY name'
        );
        $statement->execute(array_values($slugs));

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
