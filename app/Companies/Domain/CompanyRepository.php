<?php

declare(strict_types=1);

namespace App\Companies\Domain;

interface CompanyRepository
{
    public function slugExists(string $slug, ?int $exceptCompanyId = null): bool;

    public function add(Company $company): int;

    public function linkUser(int $companyId, int $userId, int $roleId): void;

    public function directory(array $filters): array;

    public function countAll(): int;

    public function findForUpdate(int $companyId): ?array;

    public function update(int $companyId, string $name, string $slug, ?string $logoPath): void;

    public function linkedUserIds(int $companyId): array;

    public function isUsersLastActiveCompany(int $userId, int $companyId): bool;

    public function deactivate(int $companyId): void;

    public function deleteMemberships(int $companyId): void;

    public function revokeUsersWithoutActiveCompanies(array $userIds, int $exceptUserId): array;
}
