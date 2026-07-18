<?php

declare(strict_types=1);

namespace App\Parking\Application;

use App\Parking\Domain\VacancyCreator;

final class CreateVacancies
{
    public function __construct(private VacancyCreator $creator) {}

    public function createVacancy(string $category, int $amount): bool
    {
        return $this->creator->createVacancy($category, $amount);
    }
}
