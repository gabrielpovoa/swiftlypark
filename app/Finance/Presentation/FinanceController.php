<?php

declare(strict_types=1);

namespace App\Finance\Presentation;

use App\Authorization\Repositories\RbacRepository;
use App\Authorization\Services\RolePermissionResolver;
use App\Context\IdentityContext;
use App\Context\TenantContext;
use App\Finance\Infrastructure\FinancialAdjustmentRepository;
use App\Finance\Infrastructure\FinancialReportRepository;
use App\Finance\Infrastructure\PdoFinancialLedgerRepository;
use App\Finance\Application\FinancialAdjustmentService;
use App\Finance\Application\FinancialAuditService;
use App\Finance\Application\FinancialReportService;
use App\Finance\Domain\FinancialScope;
use App\Repositories\AuditLogRepository;
use App\Services\AuthorizationService;
use App\Transactions\TransactionManager;
use Config\Database;
use Core\Controller;
use DomainException;
use PDO;
use Throwable;

final class FinanceController extends Controller
{
    public function index(): void
    {
        $this->authorize('finance.view');
        $this->startSession();
        $connection = (new Database())->connect();
        $authorization = new AuthorizationService(IdentityContext::current());
        $scope = $this->financialScope($connection);
        $repository = new FinancialReportRepository($connection, $scope);

        $this->setView('Finance/index', [
            'title' => 'BI Financeiro - SwiftlyPark',
            'transactions' => $repository->recentTransactions(),
            'canAdjust' => $authorization->can('finance.adjust')
                && !$scope->isGlobal()
                && TenantContext::instance()->getCompanyId() === $scope->companyId(),
            'csrfToken' => $this->csrfToken(),
            'success' => $_SESSION['finance_success'] ?? null,
            'error' => $_SESSION['finance_error'] ?? null,
            'companyBrand' => $scope->isGlobal()
                ? null
                : $this->company($connection, (int) $scope->companyId()),
            'financeCompanies' => $this->isGlobalSuperAdmin($connection)
                ? $this->companies($connection)
                : [],
            'selectedCompanyId' => $scope->companyId(),
            'isGlobalFinance' => $scope->isGlobal(),
        ]);
        unset($_SESSION['finance_success'], $_SESSION['finance_error']);
    }

    public function data(): void
    {
        $this->authorize('finance.view');
        $month = (string) ($_GET['month'] ?? date('Y-m'));

        try {
            $connection = (new Database())->connect();
            $scope = $this->financialScope($connection);
            $data = (new FinancialReportService(
                new FinancialReportRepository($connection, $scope)
            ))->dashboard($month);
            $data['scope'] = ['global' => $scope->isGlobal(), 'company_id' => $scope->companyId()];
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($data, JSON_THROW_ON_ERROR);
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            http_response_code(422);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['error' => 'Não foi possível gerar o relatório.']);
        }
    }

    public function refund(): void
    {
        $this->authorize('finance.adjust');
        $this->startSession();

        if (!$this->validCsrf()) {
            $_SESSION['finance_error'] = 'A sessão expirou. Tente novamente.';
            $this->redirect();
        }

        $connection = (new Database())->connect();

        try {
            (new FinancialAdjustmentService(
                new FinancialAdjustmentRepository($connection),
                new FinancialAuditService(
                    new AuditLogRepository($connection),
                    IdentityContext::current()
                ),
                new TransactionManager($connection),
                IdentityContext::current(),
                new PdoFinancialLedgerRepository($connection)
            ))->refund(
                (int) ($_POST['transaction_id'] ?? 0),
                (float) ($_POST['amount'] ?? 0),
                (string) ($_POST['reason'] ?? '')
            );
            $_SESSION['finance_success'] = 'Ajuste registrado com sucesso.';
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            $_SESSION['finance_error'] = $throwable instanceof \DomainException
                ? $throwable->getMessage()
                : 'Não foi possível registrar o ajuste.';
        }

        $this->redirect();
    }

    public function exportCsv(): void
    {
        $this->authorize('finance.view');
        $month = (string) ($_GET['month'] ?? date('Y-m'));

        try {
            $connection = (new Database())->connect();
            $scope = $this->financialScope($connection);
            $data = (new FinancialReportService(
                new FinancialReportRepository($connection, $scope)
            ))->dashboard($month);
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            http_response_code(422);
            echo 'Não foi possível exportar o relatório.';
            return;
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header(sprintf(
            'Content-Disposition: attachment; filename="bi-financeiro-%s.csv"',
            preg_replace('/[^0-9-]/', '', $month)
        ));

        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Indicador', 'Valor'], ';');

        foreach ($data['metrics'] as $metric => $value) {
            fputcsv($output, [
                $this->metricLabel($metric),
                $value === null ? 'Sem histórico' : (string) $value,
            ], ';');
        }

        fputcsv($output, [], ';');
        fputcsv($output, ['Faturamento por dia'], ';');
        fputcsv($output, ['Data', 'Total', 'Rotativo', 'Mensalista'], ';');

        foreach ($data['charts']['daily_revenue']['labels'] as $index => $day) {
            fputcsv($output, [
                $day,
                (string) $data['charts']['daily_revenue']['total'][$index],
                (string) $data['charts']['daily_revenue']['rotating'][$index],
                (string) $data['charts']['daily_revenue']['monthly'][$index],
            ], ';');
        }

        fputcsv($output, [], ';');
        fputcsv($output, ['Formas de pagamento'], ';');
        fputcsv($output, ['Forma', 'Valor'], ';');

        foreach ($data['charts']['payment_methods']['labels'] as $index => $method) {
            fputcsv($output, [
                $method,
                (string) $data['charts']['payment_methods']['values'][$index],
            ], ';');
        }
    }

    public function printPdf(): void
    {
        $this->authorize('finance.view');
        $month = (string) ($_GET['month'] ?? date('Y-m'));

        try {
            $connection = (new Database())->connect();
            $scope = $this->financialScope($connection);
            $data = (new FinancialReportService(
                new FinancialReportRepository($connection, $scope)
            ))->dashboard($month);
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            http_response_code(422);
            echo 'Não foi possível gerar o relatório para PDF.';
            return;
        }

        $this->setView('Finance/print', [
            'title' => 'Relatório Financeiro - SwiftlyPark',
            'month' => $month,
            'data' => $data,
            'companyBrand' => $scope->isGlobal()
                ? null
                : $this->company($connection, (int) $scope->companyId()),
        ], false);
    }

    private function authorize(string $permission): void
    {
        (new AuthorizationService(IdentityContext::current()))
            ->check($permission);
    }

    private function financialScope(PDO $connection): FinancialScope
    {
        if (!$this->isGlobalSuperAdmin($connection)) {
            $companyId = TenantContext::instance()->getCompanyId();
            if ($companyId === null) {
                throw new DomainException('Selecione uma empresa para acessar o financeiro.');
            }

            return FinancialScope::company($companyId);
        }

        $hasExplicitFilter = array_key_exists('company_id', $_GET);
        $requested = (string) ($_GET['company_id'] ?? '');
        if ($hasExplicitFilter && ($requested === '' || $requested === 'all')) {
            return FinancialScope::global();
        }

        if (!$hasExplicitFilter) {
            $supportCompanyId = filter_var(
                $_SESSION['support_impersonation']['company_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );
            if ($supportCompanyId !== false
                && $this->company($connection, (int) $supportCompanyId) !== null) {
                return FinancialScope::company((int) $supportCompanyId);
            }

            return FinancialScope::global();
        }

        $companyId = filter_var(
            $requested,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($companyId === false || $this->company($connection, (int) $companyId) === null) {
            throw new DomainException('Empresa inválida para o relatório financeiro.');
        }

        return FinancialScope::company((int) $companyId);
    }

    private function isGlobalSuperAdmin(PDO $connection): bool
    {
        $authorization = (new RolePermissionResolver(
            new RbacRepository($connection)
        ))->resolve(IdentityContext::current()->userId(), null);

        return in_array('super-admin', $authorization->roleSlugs(), true);
    }

    private function companies(PDO $connection): array
    {
        return $connection->query(
            'SELECT id, name, slug, logo_path FROM companies
             WHERE deleted_at IS NULL ORDER BY name ASC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    private function company(PDO $connection, int $companyId): ?array
    {
        $statement = $connection->prepare(
            'SELECT id, name, slug, logo_path FROM companies
             WHERE id = :company_id AND deleted_at IS NULL LIMIT 1'
        );
        $statement->execute(['company_id' => $companyId]);
        $company = $statement->fetch(PDO::FETCH_ASSOC);

        return $company === false ? null : $company;
    }

    private function csrfToken(): string
    {
        if (!isset($_SESSION['finance_csrf'])) {
            $_SESSION['finance_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['finance_csrf'];
    }

    private function validCsrf(): bool
    {
        return isset($_SESSION['finance_csrf'], $_POST['csrf_token'])
            && hash_equals(
                $_SESSION['finance_csrf'],
                (string) $_POST['csrf_token']
            );
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function redirect(): never
    {
        header('Location: /finance');
        exit;
    }

    private function metricLabel(string $metric): string
    {
        return match ($metric) {
            'gross_revenue' => 'Faturamento bruto',
            'net_revenue' => 'Faturamento líquido',
            'rotating_revenue' => 'Receita rotativa',
            'monthly_revenue' => 'Receita mensalista',
            'rotating_transactions' => 'Checkouts rotativos pagos',
            'monthly_payments' => 'Mensalidades pagas',
            'average_ticket' => 'Ticket médio',
            'occupancy_rate' => 'Taxa de ocupação',
            'adjustments_total' => 'Ajustes financeiros',
            'yoy_percentage' => 'Comparativo anual',
            default => $metric,
        };
    }
}
