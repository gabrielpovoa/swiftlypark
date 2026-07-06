<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Context\IdentityContext;
use App\Finance\Repositories\FinancialAdjustmentRepository;
use App\Finance\Repositories\FinancialReportRepository;
use App\Finance\Services\FinancialAdjustmentService;
use App\Finance\Services\FinancialAuditService;
use App\Finance\Services\FinancialReportService;
use App\Repositories\AuditLogRepository;
use App\Services\AuthorizationService;
use App\Transactions\TransactionManager;
use Config\Database;
use Core\Controller;
use Throwable;

final class FinanceController extends Controller
{
    public function index(): void
    {
        $this->authorize('finance.view');
        $this->startSession();
        $connection = (new Database())->connect();
        $authorization = new AuthorizationService(IdentityContext::current());

        $this->setView('Finance/index', [
            'title' => 'BI Financeiro - SwiftlyPark',
            'transactions' => (new FinancialReportRepository($connection))
                ->recentTransactions(),
            'canAdjust' => $authorization->can('finance.adjust'),
            'csrfToken' => $this->csrfToken(),
            'success' => $_SESSION['finance_success'] ?? null,
            'error' => $_SESSION['finance_error'] ?? null,
        ]);
        unset($_SESSION['finance_success'], $_SESSION['finance_error']);
    }

    public function data(): void
    {
        $this->authorize('finance.view');
        $month = (string) ($_GET['month'] ?? date('Y-m'));

        try {
            $connection = (new Database())->connect();
            $data = (new FinancialReportService(
                new FinancialReportRepository($connection)
            ))->dashboard($month);
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
                IdentityContext::current()
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
            $data = (new FinancialReportService(
                new FinancialReportRepository($connection)
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
        fputcsv($output, ['Data', 'Valor'], ';');

        foreach ($data['charts']['daily_revenue']['labels'] as $index => $day) {
            fputcsv($output, [
                $day,
                (string) $data['charts']['daily_revenue']['values'][$index],
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
            $data = (new FinancialReportService(
                new FinancialReportRepository($connection)
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
        ], false);
    }

    private function authorize(string $permission): void
    {
        (new AuthorizationService(IdentityContext::current()))
            ->check($permission);
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
            'average_ticket' => 'Ticket médio',
            'occupancy_rate' => 'Taxa de ocupação',
            'adjustments_total' => 'Ajustes financeiros',
            'yoy_percentage' => 'Comparativo anual',
            default => $metric,
        };
    }
}
