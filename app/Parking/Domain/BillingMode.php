<?php

declare(strict_types=1);

namespace App\Parking\Domain;

enum BillingMode: string
{
    case Monthly = 'MONTHLY';
    case Rotating = 'ROTATING';
}
