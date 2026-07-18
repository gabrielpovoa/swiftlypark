<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Finance\Domain\LedgerEntry;
use App\Shared\Domain\ValueObject\Money;

$credit = LedgerEntry::credit(5, 'ROTATING_PAYMENT', 27, Money::fromDecimal('39.56'),
    new DateTimeImmutable('2026-07-18 12:00:00'), 'Checkout', 1);
$debit = LedgerEntry::debit(5, 'ADJUSTMENT', 2, Money::fromDecimal('7.00'),
    new DateTimeImmutable('2026-07-18 12:01:00'), '<script>motivo</script>', 1);
if ($credit->entryType !== 'CREDIT' || $credit->amount->cents() !== 3956
    || $debit->entryType !== 'DEBIT' || $debit->sourceType !== 'ADJUSTMENT') {
    throw new RuntimeException('LedgerEntry não preservou direção/origem/valor.');
}
if (strlen($debit->description) > 500) {
    throw new RuntimeException('Descrição do ledger excedeu o limite.');
}
if (str_contains($debit->description, '<script>')) {
    throw new RuntimeException('Descrição do ledger não foi sanitizada.');
}
try {
    LedgerEntry::credit(0, 'INVALID', 0, Money::fromCents(0), new DateTimeImmutable(), '', null);
    throw new RuntimeException('Ledger aceitou lançamento inválido.');
} catch (DomainException) {
}
echo "Financial ledger domain test passed\n";
