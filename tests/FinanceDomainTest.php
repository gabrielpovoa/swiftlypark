<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Finance\Domain\AdjustmentType;
use App\Finance\Domain\RevenueSource;

if (RevenueSource::Rotating->value !== 'ROTATING'
    || RevenueSource::Monthly->value !== 'MONTHLY'
    || AdjustmentType::Refund->value !== 'REFUND') {
    throw new RuntimeException('Contratos persistidos de Finance foram alterados.');
}
echo "Finance domain test passed\n";
