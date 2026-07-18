<?php

declare(strict_types=1);

namespace App\Parking\Domain;

enum VacancyStatus: string
{
    case Free = 'livre';
    case Reserved = 'reservada';
}
