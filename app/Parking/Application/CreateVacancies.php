<?php

declare(strict_types=1);

namespace App\Parking\Application;

use App\Parking\Infrastructure\PdoVacancyCreator;

final class CreateVacancies
{
    public function __construct(private ?PdoVacancyCreator $creator = null)
    {
        $this->creator ??= new PdoVacancyCreator();
    }

    public function createVacancy(string $category, int $amount): bool
    {
        return $this->creator->createVacancy($category, $amount);
    }
}
