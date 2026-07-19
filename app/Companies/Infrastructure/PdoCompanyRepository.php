<?php

declare(strict_types=1);

namespace App\Companies\Infrastructure;

use App\Companies\Domain\Company;
use App\Companies\Domain\CompanyRepository;
use PDO;

final class PdoCompanyRepository implements CompanyRepository
{
    public function __construct(private readonly PDO $connection) {}

    public function slugExists(string $slug, ?int $exceptCompanyId = null): bool
    {
        $sql = 'SELECT 1 FROM companies WHERE slug = :slug';
        $parameters = ['slug' => $slug];
        if ($exceptCompanyId !== null) {
            $sql .= ' AND id <> :company_id';
            $parameters['company_id'] = $exceptCompanyId;
        }
        $statement = $this->connection->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);
        return $statement->fetchColumn() !== false;
    }

    public function add(Company $company): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO companies (name, slug, logo_path, created_at, updated_at)
             VALUES (:name, :slug, :logo_path, NOW(), NOW())'
        );
        $statement->execute([
            'name' => $company->name(),
            'slug' => $company->slug(),
            'logo_path' => $company->logoPath(),
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function linkUser(int $companyId, int $userId, int $roleId): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO company_user (company_id, user_id, role_id, created_at)
             VALUES (:company_id, :user_id, :role_id, NOW())'
        );
        $statement->execute(['company_id' => $companyId, 'user_id' => $userId, 'role_id' => $roleId]);
    }

    public function directory(array $filters, ?int $userId = null): array
    {
        $where = [];
        $parameters = [];
        if ($filters['query'] !== '') {
            $where[] = '(c.name LIKE :name OR c.slug LIKE :slug)';
            $parameters = ['name' => '%' . $filters['query'] . '%', 'slug' => '%' . $filters['query'] . '%'];
        }
        if ($filters['status'] === 'active') {
            $where[] = 'c.deleted_at IS NULL';
        } elseif ($filters['status'] === 'inactive') {
            $where[] = 'c.deleted_at IS NOT NULL';
        }
        if ($userId !== null) {
            $where[] = 'EXISTS (SELECT 1 FROM company_user access_cu
                WHERE access_cu.company_id = c.id AND access_cu.user_id = :access_user_id)';
            $parameters['access_user_id'] = $userId;
        }
        $limit = $filters['is_filtered'] ? 50 : 12;
        $statement = $this->connection->prepare(
            'SELECT c.id, c.name, c.slug, c.logo_path, c.deleted_at,
             COUNT(DISTINCT cu.user_id) AS users_count,
             SUM(CASE WHEN u.deleted_at IS NULL THEN 1 ELSE 0 END) AS active_users_count,
             SUM(CASE WHEN u.deleted_at IS NULL AND u.password_reset_required = 1 THEN 1 ELSE 0 END) AS password_reset_users_count,
             GROUP_CONCAT(DISTINCT r.slug ORDER BY r.slug SEPARATOR ", ") AS role_slugs
             FROM companies c LEFT JOIN company_user cu ON cu.company_id = c.id
             LEFT JOIN usuario u ON u.id_usuario = cu.user_id LEFT JOIN roles r ON r.id = cu.role_id '
             . ($where === [] ? '' : 'WHERE ' . implode(' AND ', $where)) . '
             GROUP BY c.id, c.name, c.slug, c.logo_path, c.deleted_at
             ORDER BY c.name ASC, c.id ASC LIMIT ' . $limit
        );
        $statement->execute($parameters);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll(?int $userId = null): int
    {
        if ($userId === null) {
            return (int) $this->connection->query('SELECT COUNT(*) FROM companies')->fetchColumn();
        }

        $statement = $this->connection->prepare(
            'SELECT COUNT(DISTINCT c.id) FROM companies c
             INNER JOIN company_user cu ON cu.company_id = c.id
             WHERE cu.user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    public function findForUpdate(int $companyId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, slug, logo_path, deleted_at FROM companies WHERE id = :company_id FOR UPDATE'
        );
        $statement->execute(['company_id' => $companyId]);
        $company = $statement->fetch(PDO::FETCH_ASSOC);
        return $company === false ? null : $company;
    }

    public function update(int $companyId, string $name, string $slug, ?string $logoPath): void
    {
        $statement = $this->connection->prepare(
            'UPDATE companies SET name = :name, slug = :slug,
             logo_path = COALESCE(:logo_path, logo_path), updated_at = NOW(6) WHERE id = :company_id'
        );
        $statement->execute(['name' => $name, 'slug' => $slug, 'logo_path' => $logoPath, 'company_id' => $companyId]);
    }

    public function linkedUserIds(int $companyId): array
    {
        $statement = $this->connection->prepare('SELECT user_id FROM company_user WHERE company_id = :company_id');
        $statement->execute(['company_id' => $companyId]);
        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function isUsersLastActiveCompany(int $userId, int $companyId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM company_user cu INNER JOIN companies c
             ON c.id = cu.company_id AND c.deleted_at IS NULL WHERE cu.user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);
        $count = (int) $statement->fetchColumn();
        $statement = $this->connection->prepare(
            'SELECT 1 FROM company_user WHERE user_id = :user_id AND company_id = :company_id LIMIT 1'
        );
        $statement->execute(['user_id' => $userId, 'company_id' => $companyId]);
        return $count <= 1 && $statement->fetchColumn() !== false;
    }

    public function deactivate(int $companyId): void
    {
        $statement = $this->connection->prepare(
            'UPDATE companies SET deleted_at = UTC_TIMESTAMP(6), updated_at = NOW(6)
             WHERE id = :company_id AND deleted_at IS NULL'
        );
        $statement->execute(['company_id' => $companyId]);
    }

    public function deleteMemberships(int $companyId): void
    {
        $statement = $this->connection->prepare('DELETE FROM company_user WHERE company_id = :company_id');
        $statement->execute(['company_id' => $companyId]);
    }

    public function revokeUsersWithoutActiveCompanies(array $userIds, int $exceptUserId): array
    {
        $revoked = [];
        $check = $this->connection->prepare(
            'SELECT COUNT(*) FROM company_user cu INNER JOIN companies c
             ON c.id = cu.company_id AND c.deleted_at IS NULL WHERE cu.user_id = :user_id'
        );
        $revoke = $this->connection->prepare(
            'UPDATE usuario SET deleted_at = UTC_TIMESTAMP(6)
             WHERE id_usuario = :user_id AND deleted_at IS NULL'
        );
        foreach (array_values(array_unique(array_map('intval', $userIds))) as $userId) {
            if ($userId === $exceptUserId) { continue; }
            $check->execute(['user_id' => $userId]);
            if ((int) $check->fetchColumn() > 0) { continue; }
            $revoke->execute(['user_id' => $userId]);
            if ($revoke->rowCount() > 0) { $revoked[] = $userId; }
        }
        return $revoked;
    }
}
