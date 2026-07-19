<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure;

use PDO;

final class IdentityManagementRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function paginate(int $page, int $perPage, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        [$whereSql, $parameters] = $this->userFilterSql($filters);
        $statement = $this->connection->prepare(
            'SELECT
                u.id_usuario, u.nome, u.email, u.deleted_at,
                GROUP_CONCAT(DISTINCT r.slug ORDER BY r.display_priority) AS roles,
                GROUP_CONCAT(
                    DISTINCT CONCAT(
                        c.name,
                        COALESCE(CONCAT(\' · \', tenant_role.slug), \' · sem papel\')
                    )
                    ORDER BY c.name
                    SEPARATOR \', \'
                ) AS companies
             FROM usuario u
             LEFT JOIN user_roles ur ON ur.user_id = u.id_usuario
             LEFT JOIN roles r ON r.id = ur.role_id
             LEFT JOIN (
                SELECT user_id, company_id, MAX(role_id) AS role_id
                FROM company_user
                GROUP BY user_id, company_id
             ) cu ON cu.user_id = u.id_usuario
             LEFT JOIN companies c ON c.id = cu.company_id
             LEFT JOIN roles tenant_role ON tenant_role.id = cu.role_id
             ' . $whereSql . '
             GROUP BY u.id_usuario, u.nome, u.email, u.deleted_at
             ORDER BY u.nome ASC
             LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue(
                ':' . $key,
                $value,
                $key === 'company_id' ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countUsers(array $filters = []): int
    {
        [$whereSql, $parameters] = $this->userFilterSql($filters);
        $statement = $this->connection->prepare(
            'SELECT COUNT(DISTINCT u.id_usuario)
             FROM usuario u
             LEFT JOIN (
                SELECT user_id, company_id, MAX(role_id) AS role_id
                FROM company_user
                GROUP BY user_id, company_id
             ) cu ON cu.user_id = u.id_usuario
             LEFT JOIN companies c ON c.id = cu.company_id
             ' . $whereSql
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue(
                ':' . $key,
                $value,
                $key === 'company_id' ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function companiesForFilter(?int $companyId = null): array
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, slug, deleted_at FROM companies'
            . ($companyId === null ? '' : ' WHERE id = :company_id')
            . ' ORDER BY deleted_at IS NOT NULL ASC, name ASC'
        );
        $statement->execute($companyId === null ? [] : ['company_id' => $companyId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activeCompanies(?int $companyId = null): array
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, slug
             FROM companies
             WHERE deleted_at IS NULL'
             . ($companyId === null ? '' : ' AND id = :company_id') . '
             ORDER BY name ASC'
        );
        $statement->execute($companyId === null ? [] : ['company_id' => $companyId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function assignableRoles(bool $canAssignPrivileged): array
    {
        $where = $canAssignPrivileged
            ? 'WHERE is_active = 1'
            : "WHERE is_active = 1 AND slug <> 'super-admin'";

        return $this->connection->query(
            'SELECT id, slug, name, label
             FROM roles ' . $where . '
             ORDER BY display_priority ASC, name ASC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findUserForUpdate(int $userId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id_usuario, id_login, nome, email, deleted_at
             FROM usuario WHERE id_usuario = :user_id FOR UPDATE'
        );
        $statement->execute(['user_id' => $userId]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user === false ? null : $user;
    }

    public function hasCompanyMembership(int $userId, int $companyId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM company_user
             WHERE user_id = :user_id AND company_id = :company_id LIMIT 1'
        );
        $statement->execute(['user_id' => $userId, 'company_id' => $companyId]);

        return $statement->fetchColumn() !== false;
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

    public function countActiveSuperAdminsForUpdate(): int
    {
        $statement = $this->connection->query(
            "SELECT DISTINCT u.id_usuario
             FROM usuario u
             INNER JOIN user_roles ur ON ur.user_id = u.id_usuario
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE r.slug = 'super-admin' AND u.deleted_at IS NULL
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

    public function reactivate(int $userId, string $passwordHash): void
    {
        $statement = $this->connection->prepare(
            'UPDATE usuario
             SET deleted_at = NULL,
                 senha_hash = :password_hash,
                 password_reset_required = 1
             WHERE id_usuario = :user_id AND deleted_at IS NOT NULL'
        );
        $statement->execute([
            'password_hash' => $passwordHash,
            'user_id' => $userId,
        ]);
    }

    public function updateLoginPassword(int $loginId, string $passwordHash): void
    {
        $statement = $this->connection->prepare(
            'UPDATE login
             SET senha = :password_hash
             WHERE id_login = :login_id'
        );
        $statement->execute([
            'password_hash' => $passwordHash,
            'login_id' => $loginId,
        ]);
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

    public function companyPermissionIds(int $userId, int $companyId): array
    {
        $statement = $this->connection->prepare(
            'SELECT permission_id FROM company_user_permissions
             WHERE user_id = :user_id AND company_id = :company_id'
        );
        $statement->execute(['user_id' => $userId, 'company_id' => $companyId]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function companyRolePermissionIds(int $userId, int $companyId): array
    {
        $statement = $this->connection->prepare(
            'SELECT DISTINCT rp.permission_id
             FROM company_user cu
             INNER JOIN roles r ON r.id = cu.role_id AND r.is_active = 1
             INNER JOIN role_permissions rp ON rp.role_id = r.id
             INNER JOIN permissions p ON p.id = rp.permission_id AND p.is_active = 1
             WHERE cu.user_id = :user_id AND cu.company_id = :company_id'
        );
        $statement->execute(['user_id' => $userId, 'company_id' => $companyId]);

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

    public function syncCompanyPermissions(
        int $userId,
        int $companyId,
        array $permissionIds,
        int $grantedBy
    ): void {
        $delete = $this->connection->prepare(
            'DELETE FROM company_user_permissions
             WHERE user_id = :user_id AND company_id = :company_id'
        );
        $delete->execute(['user_id' => $userId, 'company_id' => $companyId]);

        $insert = $this->connection->prepare(
            'INSERT INTO company_user_permissions (user_id, company_id, permission_id, granted_by)
             SELECT :user_id, :company_id, id, :granted_by FROM permissions
             WHERE id = :permission_id AND is_active = 1'
        );
        foreach (array_unique($permissionIds) as $permissionId) {
            $insert->execute([
                'user_id' => $userId,
                'company_id' => $companyId,
                'permission_id' => $permissionId,
                'granted_by' => $grantedBy,
            ]);
        }
    }

    public function activeCompanyMembershipsCount(int $userId): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM company_user cu
             INNER JOIN companies c ON c.id = cu.company_id AND c.deleted_at IS NULL
             WHERE cu.user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    private function userFilterSql(array $filters): array
    {
        $where = [];
        $parameters = [];

        if (($filters['query'] ?? '') !== '') {
            $where[] = '(u.nome LIKE :name_query OR u.email LIKE :email_query)';
            $parameters['name_query'] = '%' . $filters['query'] . '%';
            $parameters['email_query'] = '%' . $filters['query'] . '%';
        }

        if (($filters['company_id'] ?? null) !== null) {
            $where[] = 'cu.company_id = :company_id';
            $parameters['company_id'] = (int) $filters['company_id'];
        }

        if (($filters['exclude_super_admin'] ?? false) === true) {
            $where[] = 'NOT EXISTS (
                SELECT 1 FROM user_roles privileged_ur
                INNER JOIN roles privileged_role
                    ON privileged_role.id = privileged_ur.role_id
                   AND privileged_role.slug = "super-admin"
                WHERE privileged_ur.user_id = u.id_usuario
            )';
        }

        if (($filters['status'] ?? 'all') === 'active') {
            $where[] = 'u.deleted_at IS NULL';
        } elseif (($filters['status'] ?? 'all') === 'revoked') {
            $where[] = 'u.deleted_at IS NOT NULL';
        }

        return [
            $where === [] ? '' : 'WHERE ' . implode(' AND ', $where),
            $parameters,
        ];
    }
}
