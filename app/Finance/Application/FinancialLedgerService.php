<?php
declare(strict_types=1);
namespace App\Finance\Application;
use App\Finance\Domain\FinancialLedgerRepository;
use App\Shared\Domain\ValueObject\DatePeriod;
final class FinancialLedgerService
{
    public function __construct(private readonly FinancialLedgerRepository $ledger) {}
    public function balance(int $companyId, DatePeriod $period): array
    {
        return $this->ledger->balance($companyId, $period->start()->format('Y-m-d H:i:s'),
            $period->end()->format('Y-m-d H:i:s'));
    }
    public function reconciliation(int $companyId): array { return $this->ledger->reconciliation($companyId); }
}
