<?php

declare(strict_types=1);

namespace App\Billing\Domain;

interface PricingRepository
{
    public function findTariff(int $companyId, string $vehicleType): ?array;

    public function isMonthlyCompany(int $companyId): bool;
}
