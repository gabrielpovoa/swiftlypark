<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Shared\Domain\ValueObject\DatePeriod;
use App\Shared\Domain\ValueObject\DurationMinutes;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\VehiclePlate;

$total = Money::fromDecimal('25.00')->add(Money::fromDecimal('7.28')->multiply(2));
if ($total->decimal() !== '39.56' || $total->cents() !== 3956) {
    throw new RuntimeException('Money produziu aritmética incorreta.');
}
$legacy = VehiclePlate::fromString('bra-2k27');
$formatted = VehiclePlate::fromString('BRA2K27');
if (!$legacy->equals($formatted) || $legacy->value() !== 'BRA-2K27') {
    throw new RuntimeException('VehiclePlate não normalizou/equalizou a placa.');
}
$start = new DateTimeImmutable('2026-07-01 00:00:00');
$end = new DateTimeImmutable('2026-08-01 00:00:00');
$period = new DatePeriod($start, $end);
if (!$period->contains(new DateTimeImmutable('2026-07-31 23:59:59')) || $period->contains($end)) {
    throw new RuntimeException('DatePeriod não respeita fim exclusivo.');
}
if ((new DurationMinutes(113))->value() !== 113) {
    throw new RuntimeException('DurationMinutes alterou a duração.');
}
foreach ([static fn () => Money::fromDecimal('1.999'), static fn () => VehiclePlate::fromString('<script>'),
    static fn () => new DurationMinutes(-1), static fn () => new DatePeriod($end, $start)] as $invalid) {
    try { $invalid(); throw new RuntimeException('Value object aceitou valor inválido.'); }
    catch (DomainException) {}
}
echo "Shared value objects test passed\n";
