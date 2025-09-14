<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\VacancyModel;
use http\Header;

class VacancyController extends Controller
{
    /**
     * Lista vagas disponíveis e envia para a view
     */
    public function index()
    {
        $model = new VacancyModel();
        $counts = $model->getAvailableCounts();

        $this->setview('Vacancy/vacancy', [
            'title'  => 'Vagas Disponíveis',
            'counts' => $counts
        ]);
    }

    public function apply()
    {
        $model = new VacancyModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['type'] ?? 'carro';
            $ownerName = trim($_POST['owner_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $plate = trim($_POST['plate'] ?? '');
            $paidAmount = $_POST['paid_amount'] ?? '';
            $entryTime = $_POST['entry_time'] ?? '';
            $exitTime = $_POST['exit_time'] ?? null;

            // Validação básica
            if (!$ownerName || !$phone || !$plate || !$paidAmount || !$entryTime) {
                echo "<h1>Erro: Campos obrigatórios não preenchidos.</h1>";
                exit;
            }

            // Busca vaga livre pela categoria
            $vagaLivre = $model->getFreeVagaByCategory($type);
            if (!$vagaLivre) {
                echo "<h1>Desculpe, não há vagas livres para essa categoria no momento.</h1>";
                exit;
            }

            $idVaga = $vagaLivre['id_vaga'];

            // Prepara horários completos com data atual
            $today = date('Y-m-d');
            $horaEntrada = date('Y-m-d H:i:s', strtotime("$today $entryTime"));
            $horaSaida = $exitTime ? date('Y-m-d H:i:s', strtotime("$today $exitTime")) : null;

            try {
                // Reserva vaga e insere registros usando transação
                $model->ocuparVagaComPagamento(
                    $idVaga,
                    $horaEntrada,
                    $horaSaida,
                    $ownerName,
                    $phone,
                    $plate,
                    (float)$paidAmount
                );

                // Redireciona para manage já preenchendo filtro de placa
                header('Location: /vacancy/manage?placa=' . urlencode($plate));
                exit();
            } catch (\Exception $e) {
                echo "<h1>Erro ao reservar a vaga: " . htmlspecialchars($e->getMessage()) . "</h1>";
                exit;
            }
        }

        // Se for GET, exibe o formulário com uma vaga livre
        $type = $_GET['type'] ?? 'carro';
        $vagaLivre = $model->getFreeVagaByCategory($type);

        if (!$vagaLivre) {
            echo "<h1>Desculpe, não há vagas livres para essa categoria no momento.</h1>";
            exit;
        }

        $vacancyDetails = $model->getVacancyByType($type);

        $this->setview('Vacancy/apply', [
            'title' => $vacancyDetails['title'],
            'details' => $vacancyDetails,
            'id_vaga' => $vagaLivre['id_vaga'],
        ]);
    }

    public function manage()
    {
        $model = new VacancyModel();

        // Recebe filtros via GET
        $categoria = $_GET['categoria'] ?? null;
        $placa = $_GET['placa'] ?? null;

        $vagas = $model->getVagasFiltradas($categoria, $placa);

        $this->setview('Vacancy/manage', [
            'title'  => 'Gerenciamento de Vagas',
            'vagas'  => $vagas,
            'filtros' => [
                'categoria' => $categoria,
                'placa' => $placa
            ]
        ]);
    }
    public function finishVacancy()
    {
        // Sempre responder JSON
        header('Content-Type: application/json; charset=UTF-8');

        // 1) Tenta ler de $_POST (FormData)
        $idVaga    = $_POST['id_vaga']   ?? null;
        $horaSaida = $_POST['hora_saida'] ?? null;

        // 2) Se não veio em $_POST, tenta JSON cru
        if (!$idVaga || !$horaSaida) {
            $raw = file_get_contents('php://input');
            if ($raw) {
                $data = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    $idVaga    = $idVaga    ?: ($data['id_vaga']    ?? null);
                    $horaSaida = $horaSaida ?: ($data['hora_saida'] ?? null);
                }
            }
        }

        // Debug opcional
        // file_put_contents(__DIR__ . '/../../../storage/debug_finish.log', json_encode([
        //     '_POST' => $_POST,
        //     'idVaga' => $idVaga,
        //     'horaSaida' => $horaSaida
        // ], JSON_PRETTY_PRINT));

        if (!$idVaga || !$horaSaida) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos (id ou hora faltando).']);
            return;
        }

        try {
            $model = new \App\Models\VacancyModel();

            $vaga = $model->getVagaById($idVaga);
            if (!$vaga) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Vaga não encontrada.']);
                return;
            }
            if ($vaga['status'] === 'livre') {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Vaga já está livre.']);
                return;
            }

            // Importante: aqui esperamos "HH:mm". A model monta a data completa.
            $model->finalizarVaga($idVaga, $horaSaida);

            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            // Nunca vaze HTML; sempre JSON
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Exceção: ' . $e->getMessage()]);
        }
    }



}
