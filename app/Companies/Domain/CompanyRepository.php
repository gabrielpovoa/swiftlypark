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
}
