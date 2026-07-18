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

    public function directory(array $filters): array
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

    public function countAll(): int
    {
        return (int) $this->connection->query('SELECT COUNT(*) FROM companies')->fetchColumn();
    }
}
