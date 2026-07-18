<?php
declare(strict_types=1);
namespace App\Finance\Infrastructure;
use App\Finance\Domain\FinancialLedgerRepository;
use App\Finance\Domain\LedgerEntry;
use PDO;
final class PdoFinancialLedgerRepository implements FinancialLedgerRepository
{
    public function __construct(private readonly PDO $connection) {}
    public function append(LedgerEntry $entry): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO financial_ledger_entries (company_id, source_type, source_id, entry_type,
             amount, occurred_at, description, created_by) VALUES (:company_id, :source_type,
             :source_id, :entry_type, :amount, :occurred_at, :description, :created_by)'
        );
        $statement->execute(['company_id' => $entry->companyId, 'source_type' => $entry->sourceType,
            'source_id' => $entry->sourceId, 'entry_type' => $entry->entryType,
            'amount' => $entry->amount->decimal(), 'occurred_at' => $entry->occurredAt->format('Y-m-d H:i:s.u'),
            'description' => $entry->description, 'created_by' => $entry->createdBy]);
        return (int) $this->connection->lastInsertId();
    }

    public function balance(int $companyId, string $startUtc, string $endUtc): array
    {
        $statement = $this->connection->prepare(
            'SELECT COALESCE(SUM(CASE WHEN entry_type = "CREDIT" THEN amount ELSE 0 END), 0) credits,
             COALESCE(SUM(CASE WHEN entry_type = "DEBIT" THEN amount ELSE 0 END), 0) debits,
             COALESCE(SUM(CASE WHEN entry_type = "CREDIT" THEN amount ELSE -amount END), 0) balance
             FROM financial_ledger_entries WHERE company_id = :company_id
             AND occurred_at >= :start_at AND occurred_at < :end_at'
        );
        $statement->execute(['company_id' => $companyId, 'start_at' => $startUtc, 'end_at' => $endUtc]);
        return $statement->fetch(PDO::FETCH_ASSOC) ?: ['credits' => 0, 'debits' => 0, 'balance' => 0];
    }

    public function reconciliation(int $companyId): array
    {
        $statement = $this->connection->prepare(
            'SELECT
             (SELECT COUNT(*) FROM transacoes t WHERE t.company_id = :company_a AND t.valor > 0) rotating_sources,
             (SELECT COUNT(*) FROM financial_ledger_entries l WHERE l.company_id = :company_b AND l.source_type = "ROTATING_PAYMENT") rotating_entries,
             (SELECT COUNT(*) FROM monthly_contract_payments m WHERE m.company_id = :company_c AND m.amount > 0) monthly_sources,
             (SELECT COUNT(*) FROM financial_ledger_entries l WHERE l.company_id = :company_d AND l.source_type = "MONTHLY_PAYMENT") monthly_entries,
             (SELECT COUNT(*) FROM financial_adjustments a WHERE a.company_id = :company_e AND a.amount > 0) adjustment_sources,
             (SELECT COUNT(*) FROM financial_ledger_entries l WHERE l.company_id = :company_f AND l.source_type = "ADJUSTMENT") adjustment_entries'
        );
        $statement->execute(['company_a' => $companyId, 'company_b' => $companyId,
            'company_c' => $companyId, 'company_d' => $companyId,
            'company_e' => $companyId, 'company_f' => $companyId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        $row['consistent'] = (int) ($row['rotating_sources'] ?? -1) === (int) ($row['rotating_entries'] ?? -2)
            && (int) ($row['monthly_sources'] ?? -1) === (int) ($row['monthly_entries'] ?? -2)
            && (int) ($row['adjustment_sources'] ?? -1) === (int) ($row['adjustment_entries'] ?? -2);
        return $row;
    }
}
