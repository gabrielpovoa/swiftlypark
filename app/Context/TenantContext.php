<?php

declare(strict_types=1);

namespace App\Context;

use App\Companies\Domain\Company;

final class TenantContext
{
    private static ?self $instance = null;
    private ?Company $company = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function getCompanyId(): ?int
    {
        return $this->company?->id();
    }

    public function clear(): void
    {
        $this->company = null;
    }

    public static function clearStatic(): void
    {
        self::instance()->clear();
    }
}
