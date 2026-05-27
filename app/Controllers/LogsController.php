<?php

namespace App\Controllers;

use App\Models\LogsModel;
use Core\Controller;

class LogsController extends Controller
{
    /**
     * Retorna os filtros em JSON para o SweetAlert
     */
    public function options()
    {
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

        } catch (\Exception $e) {
            http_response_code(500);

            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'error' => true,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }
    }

    /**
     * Página de impressão dos logs
     */
    public function print()
    {
        $filter = $_GET['filter'] ?? '';
        $model = new LogsModel();

        if (empty($filter)) {
            // Sem filtro → busca todos
            $logs = $model->getAllLogs();
        } else {
            // Com filtro → busca filtrado
            $logs = $model->getLogsByFilter($filter);
        }

        $this->setView(
            'Logs/print',
            ['logs' => $logs],
            false
        );
    }
}