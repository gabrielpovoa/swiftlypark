<?php

declare(strict_types=1);

namespace App\Finance\Domain;

enum AdjustmentType: string
{
    case Refund = 'REFUND';
}
