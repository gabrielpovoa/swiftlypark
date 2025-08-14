<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\VacancyModel;

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

                // Confirmação para o usuário
                echo "<h1>Inscrição confirmada!</h1>";
                echo "<p><strong>Nome:</strong> " . htmlspecialchars($ownerName) . "</p>";
                echo "<p><strong>Telefone:</strong> " . htmlspecialchars($phone) . "</p>";
                echo "<p><strong>Placa:</strong> " . htmlspecialchars(strtoupper($plate)) . "</p>";
                echo "<p><strong>Vaga ID:</strong> " . $idVaga . "</p>";
                echo "<p><strong>Valor pago pela vaga:</strong> R$ " . number_format($paidAmount, 2, ',', '.') . "</p>";
                echo "<p><strong>Horário de entrada:</strong> " . date('H:i', strtotime($horaEntrada)) . "</p>";
                echo "<p><strong>Horário de saída:</strong> " . ($exitTime ? date('H:i', strtotime($horaSaida)) : 'Não informado') . "</p>";
            } catch (\Exception $e) {
                echo "<h1>Erro ao reservar a vaga: " . htmlspecialchars($e->getMessage()) . "</h1>";
            }

            exit;
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

}
