<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TenantRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function findCompanyById(int $companyId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, slug, logo_path FROM companies
             WHERE id = :company_id AND deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute(['company_id' => $companyId]);

        $company = $statement->fetch(PDO::FETCH_ASSOC);

        return $company === false ? null : $company;
    }

    public function findCompanyBySlug(string $slug): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, slug, logo_path FROM companies
             WHERE slug = :slug AND deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);

        $company = $statement->fetch(PDO::FETCH_ASSOC);

        return $company === false ? null : $company;
    }

    public function hasMembership(int $userId, int $companyId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1
             FROM company_user cu
             INNER JOIN roles r ON r.id = cu.role_id AND r.is_active = 1
             INNER JOIN companies c ON c.id = cu.company_id AND c.deleted_at IS NULL
             INNER JOIN usuario u ON u.id_usuario = cu.user_id AND u.deleted_at IS NULL
             WHERE cu.user_id = :user_id AND cu.company_id = :company_id
             LIMIT 1'
        );
        $statement->execute([
            'user_id' => $userId,
            'company_id' => $companyId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function findCompaniesForUser(int $userId): array
    {
        $statement = $this->connection->prepare(
            'SELECT
                c.id,
                c.name,
                c.slug,
                c.logo_path,
                r.slug AS role_slug,
                r.label AS role_label
             FROM companies c
             INNER JOIN company_user cu ON cu.company_id = c.id
             INNER JOIN usuario u ON u.id_usuario = cu.user_id AND u.deleted_at IS NULL
             INNER JOIN roles r ON r.id = cu.role_id AND r.is_active = 1
             WHERE cu.user_id = :user_id
               AND c.deleted_at IS NULL
             ORDER BY cu.created_at DESC, c.name ASC, c.id ASC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findActiveCompanies(): array
    {
        $statement = $this->connection->query(
            'SELECT
                c.id,
                c.name,
                c.slug,
                c.logo_path,
                NULL AS role_slug,
                NULL AS role_label
             FROM companies c
             WHERE c.deleted_at IS NULL
             ORDER BY c.name ASC, c.id ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findSwitchableCompaniesForPlatformUser(int $userId): array
    {
        $statement = $this->connection->prepare(
            'SELECT
                c.id,
                c.name,
                c.slug,
                c.logo_path,
                r.slug AS role_slug,
                r.label AS role_label,
                CASE WHEN cu.user_id IS NULL THEN 0 ELSE 1 END AS has_membership
             FROM companies c
             LEFT JOIN company_user cu
                ON cu.company_id = c.id
               AND cu.user_id = :user_id
             LEFT JOIN roles r
                ON r.id = cu.role_id
               AND r.is_active = 1
             WHERE c.deleted_at IS NULL
             ORDER BY has_membership DESC, c.name ASC, c.id ASC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findFirstCompanyForUser(int $userId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT c.id, c.name, c.slug, c.logo_path
             FROM companies c
             INNER JOIN company_user cu ON cu.company_id = c.id
             INNER JOIN roles r ON r.id = cu.role_id AND r.is_active = 1
             WHERE cu.user_id = :user_id
               AND c.deleted_at IS NULL
             ORDER BY cu.created_at DESC, (c.slug = :preferred_slug) DESC, c.id ASC
             LIMIT 1'
        );
        $statement->execute([
            'user_id' => $userId,
            'preferred_slug' => 'swiftlypark',
        ]);

        $company = $statement->fetch(PDO::FETCH_ASSOC);

        return $company === false ? null : $company;
    }
}
