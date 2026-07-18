<?php
declare(strict_types=1);
namespace App\Parking\Domain;
interface VacancyCreator
{
    public function createVacancy(string $category, int $amount): bool;
}
