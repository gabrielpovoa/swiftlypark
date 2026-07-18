<?php

declare(strict_types=1);

namespace App\Finance\Domain;

enum RevenueSource: string
{
    case Rotating = 'ROTATING';
    case Monthly = 'MONTHLY';
}
