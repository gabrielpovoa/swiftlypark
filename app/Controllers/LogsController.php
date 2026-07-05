<?php

namespace App\Controllers;

use App\Context\IdentityContext;
use App\Models\LogsModel;
use App\Services\AuthorizationService;
use Core\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LogsController extends Controller
{
    /**
     * Retorna os filtros em JSON para o SweetAlert
     */
    public function options()
    {
        $this->authorizeReports();

        try {
            $model = new LogsModel();
            $data = $model->getFilters();

            // Garantir retorno JSON puro
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            exit;

        } catch (\Throwable $e) {
            error_log('Falha ao carregar períodos do relatório: ' . $e->getMessage());
            http_response_code(500);

            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'error' => true,
                'message' => 'Não foi possível carregar os períodos.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }
    }

    /**
     * Página de impressão dos logs
     */
    public function print()
    {
        $this->authorizeReports();

        $timezone = new \DateTimeZone('America/Sao_Paulo');
        $filter = $_GET['filter']
            ?? (new \DateTimeImmutable('now', $timezone))->format('Y-m');
        $monthStart = \DateTimeImmutable::createFromFormat('!Y-m', $filter, $timezone);

        if (!$monthStart || $monthStart->format('Y-m') !== $filter) {
            http_response_code(422);
            exit('Período inválido.');
        }

        $nextMonthStart = $monthStart->modify('first day of next month');
        $model = new LogsModel();
        $logs = $model->getLogsByPeriod(
            $monthStart->format('Y-m-d H:i:s'),
            $nextMonthStart->format('Y-m-d H:i:s')
        );

        $shouldExport = isset($_GET['download'])
            || isset($_GET['export'])
            || (isset($_GET['format']) && $_GET['format'] === 'xlsx');

        if ($shouldExport) {
            $this->exportLogsToXlsx($logs, $filter);
            return;
        }

        $this->setView(
            'Logs/print',
            [
                'logs' => $logs,
                'periodLabel' => $monthStart->format('m/Y'),
                'filter' => $filter,
            ],
            false
        );
    }

    private function exportLogsToXlsx(array $logs, string $filter): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Relatorio-logs');
        $sheet->fromArray([
            ['Data', 'Entrada', 'Saída', 'Placa', 'Valor Pago', 'Cliente']
        ], null, 'A1');

        $row = 2;
        foreach ($logs as $log) {
            $sheet->fromArray([
                $log['data'] ?? '',
                $log['hora_entrada'] ?? '',
                $log['hora_saida'] ?? '',
                $log['placa'] ?? '',
                (float) ($log['valor_pago'] ?? 0),
                $log['nome_cliente'] ?? '',
            ], null, 'A' . $row);

            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(40);
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9EAF7');
        $sheet->freezePane('A2');

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="relatorio-logs-' . $filter . '.xlsx"');
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function authorizeReports(): void
    {
        (new AuthorizationService(IdentityContext::current()))
            ->check('report.view');
    }
}
