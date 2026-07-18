<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Companies\Domain\Company;
use App\Context\TenantContext;
use App\Finance\Domain\LedgerEntry;
use App\Finance\Infrastructure\FinancialReportRepository;
use App\Finance\Infrastructure\PdoFinancialLedgerRepository;
use App\Shared\Domain\ValueObject\Money;
use Config\Database;

$connection = (new Database())->connect();
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$company = $connection->query(
    'SELECT id, name, slug, logo_path FROM companies WHERE deleted_at IS NULL ORDER BY id LIMIT 1'
)->fetch(PDO::FETCH_ASSOC);
$otherCompanyId = $connection->query(
    'SELECT id FROM companies WHERE deleted_at IS NULL ORDER BY id LIMIT 1 OFFSET 1'
)->fetchColumn();
$userId = $connection->query('SELECT id_usuario FROM usuario WHERE deleted_at IS NULL ORDER BY id_usuario LIMIT 1')
    ->fetchColumn();
if ($company === false || $userId === false) {
    throw new RuntimeException('Fixtures mínimas de empresa/usuário não estão disponíveis.');
}

$companyId = (int) $company['id'];
$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$start = $now->modify('-1 minute')->format('Y-m-d H:i:s');
$end = $now->modify('+1 minute')->format('Y-m-d H:i:s');
$sourceBase = random_int(800000000, 899999999);
$ledger = new PdoFinancialLedgerRepository($connection);
$connection->beginTransaction();

try {
    $before = $ledger->balance($companyId, $start, $end);
    $ledger->append(LedgerEntry::credit($companyId, 'ROTATING_PAYMENT', $sourceBase,
        Money::fromDecimal('39.56'), $now, 'Teste integração crédito', (int) $userId));
    $ledger->append(LedgerEntry::debit($companyId, 'ADJUSTMENT', $sourceBase + 1,
        Money::fromDecimal('7.00'), $now, 'Teste integração débito', (int) $userId));
    $after = $ledger->balance($companyId, $start, $end);
    $delta = (float) $after['balance'] - (float) $before['balance'];
    if (abs($delta - 32.56) > 0.001) {
        throw new RuntimeException('Saldo do ledger divergiu: ' . $delta);
    }

    try {
        $ledger->append(LedgerEntry::credit($companyId, 'ROTATING_PAYMENT', $sourceBase,
            Money::fromDecimal('39.56'), $now, 'Duplicado', (int) $userId));
        throw new RuntimeException('Origem duplicada foi aceita pelo ledger.');
    } catch (PDOException $exception) {
        if ((string) $exception->getCode() !== '23000') {
            throw $exception;
        }
    }

    TenantContext::instance()->setCompany(Company::reconstitute(
        $companyId, (string) $company['name'], (string) $company['slug'],
        $company['logo_path'] !== null ? (string) $company['logo_path'] : null, true
    ));
    $summary = (new FinancialReportRepository($connection))->summary($start, $end);
    if ((float) $summary['gross_revenue'] + 0.001 < (float) $after['credits']) {
        throw new RuntimeException('Relatório não leu os créditos do ledger.');
    }

    if ($otherCompanyId !== false) {
        $other = $ledger->balance((int) $otherCompanyId, $start, $end);
        if (abs((float) $other['balance'] - $delta) < 0.001 && abs($delta) > 0.001) {
            throw new RuntimeException('Lançamento vazou para outra empresa.');
        }
    }

    echo "Financial ledger MySQL integration test passed\n";
} finally {
    TenantContext::instance()->clear();
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
}

$cleanup = $connection->prepare(
    'SELECT COUNT(*) FROM financial_ledger_entries
     WHERE company_id = :company_id AND source_id IN (:source_a, :source_b)'
);
$cleanup->execute(['company_id' => $companyId, 'source_a' => $sourceBase, 'source_b' => $sourceBase + 1]);
if ((int) $cleanup->fetchColumn() !== 0) {
    throw new RuntimeException('Rollback do teste não removeu os lançamentos temporários.');
}

$reconciliation = $ledger->reconciliation($companyId);
if (($reconciliation['consistent'] ?? false) !== true) {
    throw new RuntimeException('Backfill financeiro não está reconciliado: ' . json_encode($reconciliation));
}
echo "Financial ledger reconciliation passed\n";
