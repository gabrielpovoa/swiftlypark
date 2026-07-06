<?php

declare(strict_types=1);

namespace App\Identity\Repositories;

use PDO;

final class IdentityManagementRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function paginate(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        $statement = $this->connection->query(
            'SELECT
                u.id_usuario, u.nome, u.email, u.deleted_at,
                GROUP_CONCAT(DISTINCT r.slug ORDER BY r.display_priority) AS roles
             FROM usuario u
             LEFT JOIN user_roles ur ON ur.user_id = u.id_usuario
             LEFT JOIN roles r ON r.id = ur.role_id
             GROUP BY u.id_usuario
             ORDER BY u.nome ASC
             LIMIT ' . $perPage . ' OFFSET ' . $offset
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countUsers(): int
    {
        return (int) $this->connection
            ->query('SELECT COUNT(*) FROM usuario')
            ->fetchColumn();
    }

    public function findUserForUpdate(int $userId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id_usuario, nome, email, deleted_at
             FROM usuario WHERE id_usuario = :user_id FOR UPDATE'
        );
        $statement->execute(['user_id' => $userId]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user === false ? null : $user;
    }

    public function hasRole(int $userId, string $role): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = :user_id AND r.slug = :role LIMIT 1'
        );
        $statement->execute(['user_id' => $userId, 'role' => $role]);

        return $statement->fetchColumn() !== false;
    }

    public function countActiveMastersForUpdate(): int
    {
        $statement = $this->connection->query(
            "SELECT DISTINCT u.id_usuario
             FROM usuario u
             INNER JOIN user_roles ur ON ur.user_id = u.id_usuario
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE r.slug = 'master' AND u.deleted_at IS NULL
             FOR UPDATE"
        );

        return count($statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function revoke(int $userId): void
    {
        $statement = $this->connection->prepare(
            'UPDATE usuario SET deleted_at = UTC_TIMESTAMP(6)
             WHERE id_usuario = :user_id AND deleted_at IS NULL'
        );
        $statement->execute(['user_id' => $userId]);
    }

    public function permissions(): array
    {
        return $this->connection->query(
            'SELECT id, slug, name FROM permissions
             WHERE is_active = 1 ORDER BY name'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function directPermissionIds(int $userId): array
    {
        $statement = $this->connection->prepare(
            'SELECT permission_id FROM user_permissions WHERE user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function rolePermissionIds(int $userId): array
    {
        $statement = $this->connection->prepare(
            'SELECT DISTINCT rp.permission_id
             FROM user_roles ur
             INNER JOIN roles r
                ON r.id = ur.role_id AND r.is_active = 1
             INNER JOIN role_permissions rp ON rp.role_id = r.id
             INNER JOIN permissions p
                ON p.id = rp.permission_id AND p.is_active = 1
             WHERE ur.user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function permissionSlugs(array $permissionIds): array
    {
        if ($permissionIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($permissionIds), '?'));
        $statement = $this->connection->prepare(
            'SELECT slug FROM permissions WHERE id IN (' . $placeholders . ')
             ORDER BY slug'
        );
        $statement->execute(array_values($permissionIds));

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public function syncPermissions(
        int $userId,
        array $permissionIds,
        int $grantedBy
    ): void {
        $delete = $this->connection->prepare(
            'DELETE FROM user_permissions WHERE user_id = :user_id'
        );
        $delete->execute(['user_id' => $userId]);

        $insert = $this->connection->prepare(
            'INSERT INTO user_permissions (
                user_id, permission_id, granted_by
             )
             SELECT :user_id, id, :granted_by
             FROM permissions
             WHERE id = :permission_id AND is_active = 1'
        );

        foreach (array_unique($permissionIds) as $permissionId) {
            $insert->execute([
                'user_id' => $userId,
                'permission_id' => $permissionId,
                'granted_by' => $grantedBy,
            ]);
        }
    }
}
