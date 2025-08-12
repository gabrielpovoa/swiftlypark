<?php
        namespace App\controllers;

        use App\models\LogsModel;
        use Core\Controller;

        class LogsController extends Controller
        {
            public function options()
            {
                $model = new LogsModel();
                $data = $model->getFilters();

                header('Content-Type: application/json');
                echo json_encode($data);
                exit;
            }

            public function print()
            {
                $filter = $_GET['filter'] ?? '';
                $model = new LogsModel();

                if ($filter === '' || $filter === null) {
                    // Sem filtro, busca todos os logs
                    $logs = $model->getAllLogs();
                } else {
                    $logs = $model->getLogsByFilter($filter);
                }

                $this->setView('logs/print', ['logs' => $logs], false);
            }
        }
