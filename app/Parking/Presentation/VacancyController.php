<?php

namespace App\Parking\Presentation;

use Core\Controller;
use App\Parking\Application\ParkingService;
use App\Context\IdentityContext;
use App\Services\AuthorizationService;
use App\Security\InputSanitizer;
use DomainException;

class VacancyController extends Controller
{
    /**
     * Lista vagas disponíveis e envia para a view
     */
    public function index()
    {
        $model = new ParkingService();
        $counts = $model->getAvailableCounts();

        $this->setview('Vacancy/vacancy', [
            'title' => 'Vagas Disponíveis',
            'counts' => $counts,
            'canCheckin' => $this->authorization()->can('vehicle.checkin'),
            'canCreateVacancy' => $this->authorization()->can('vacancy.create')
        ]);
    }

    public function apply()
    {
        $model = new ParkingService();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sanitizer = new InputSanitizer();
            $type = strtolower($sanitizer->text($_POST['type'] ?? 'carro', 30));
            $ownerName = $sanitizer->text($_POST['owner_name'] ?? '', 100);
            $phone = $sanitizer->text($_POST['phone'] ?? '', 50);
            $plate = strtoupper($sanitizer->text($_POST['plate'] ?? '', 10));
            $entryTime = $sanitizer->text($_POST['entry_time'] ?? '', 5);

            if (!in_array($type, ['carro', 'moto', 'caminhao', 'app'], true)) {
                $type = 'carro';
            }

            if($model->checkIfVehicleIsParked($plate)) {
                $vacancyDetails = $model->getVacancyByType($type);
                $freeVacancy = $model->getFreeVagaByCategory($type);

                return $this->setview('Vacancy/apply', [
                    'title' => $vacancyDetails['title'],
                    'details' => $vacancyDetails,
                    'selectedType' => $type,
                    'id_vaga' => $freeVacancy['id_vaga'] ?? null,
                    'alert' => [
                        'icon' => 'warning',
                        'title' => 'Veículo já estacionado',
                        'message' => "A placa $plate já possui uma estadia em aberto.",
                    ],
                    'formData' => compact('ownerName', 'phone', 'plate', 'entryTime'),
                ]);
            }

            // Validação básica
            if (
                !$ownerName
                || !$phone
                || !$plate
                || !$entryTime
                || preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $entryTime) !== 1
            ) {
                $vacancyDetails = $model->getVacancyByType($type);
                $freeVacancy = $model->getFreeVagaByCategory($type);

                return $this->setview('Vacancy/apply', [
                    'title' => $vacancyDetails['title'],
                    'details' => $vacancyDetails,
                    'selectedType' => $type,
                    'id_vaga' => $freeVacancy['id_vaga'] ?? null,
                    'alert' => [
                        'icon' => 'warning',
                        'title' => 'Confira os dados',
                        'message' => 'Preencha todos os campos obrigatórios com informações válidas.',
                    ],
                    'formData' => compact('ownerName', 'phone', 'plate', 'entryTime'),
                ]);
            }

            // Busca vaga livre pela categoria
            $vagaLivre = $model->getFreeVagaByCategory($type);
            if (!$vagaLivre) {
                return $this->setview('Vacancy/noVacancy', [
                    'title' => 'Sem Vagas Disponíveis',
                    'type' => $type,
                    'canCreateVacancy' => $this->authorization()
                        ->can('vacancy.create')
                ]);
            }

            $idVaga = $vagaLivre['id_vaga'];

            // Prepara horários completos com data atual
            $today = date('Y-m-d');
            $horaEntrada = date('Y-m-d H:i:s', strtotime("$today $entryTime"));
            try {
                $model->ocuparVaga(
                    $idVaga,
                    $horaEntrada,
                    $ownerName,
                    $phone,
                    $plate,
                    $type
                );


                // Redireciona para manage já preenchendo filtro de placa
                header('Location: /vacancy/manage?placa=' . urlencode($plate));
                exit();
            } catch (DomainException $e) {
                $vacancyDetails = $model->getVacancyByType($type);

                return $this->setview('Vacancy/apply', [
                    'title' => $vacancyDetails['title'],
                    'details' => $vacancyDetails,
                    'selectedType' => $type,
                    'id_vaga' => $idVaga,
                    'alert' => [
                        'icon' => 'info',
                        'title' => 'Contrato mensalista necessário',
                        'message' => $e->getMessage(),
                    ],
                    'formData' => compact('ownerName', 'phone', 'plate', 'entryTime'),
                ]);
            } catch (\Throwable $e) {
                error_log($e->getMessage());
                $vacancyDetails = $model->getVacancyByType($type);

                return $this->setview('Vacancy/apply', [
                    'title' => $vacancyDetails['title'],
                    'details' => $vacancyDetails,
                    'selectedType' => $type,
                    'id_vaga' => $idVaga,
                    'alert' => [
                        'icon' => 'error',
                        'title' => 'Não foi possível concluir',
                        'message' => 'O check-in não foi realizado. Tente novamente.',
                    ],
                    'formData' => compact('ownerName', 'phone', 'plate', 'entryTime'),
                ]);
            }
        }

        // Se for GET, exibe o formulário com uma vaga livre
        $type = $_GET['type'] ?? 'carro';
        $vagaLivre = $model->getFreeVagaByCategory($type);

        if (!$vagaLivre) {
            return $this->setview('Vacancy/noVacancy', [
                'title' => 'Sem Vagas Disponíveis',
                'type' => $type,
                'canCreateVacancy' => $this->authorization()
                    ->can('vacancy.create')
            ]);
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
        $model = new ParkingService();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['vacancy_checkout_csrf'] ??= bin2hex(random_bytes(32));

        // Recebe filtros via GET
        $categoria = $_GET['categoria'] ?? null;
        $placa = $_GET['placa'] ?? null;

        $vagas = $model->getVagasFiltradas($categoria, $placa);

        $this->setview('Vacancy/manage', [
            'title' => 'Gerenciamento de Vagas',
            'vagas' => $vagas,
            'filtros' => [
                'categoria' => $categoria,
                'placa' => $placa
            ],
            'checkoutCsrfToken' => $_SESSION['vacancy_checkout_csrf'],
        ]);
    }
    public function finishVacancy()
    {
        // Sempre responder JSON
        header('Content-Type: application/json; charset=UTF-8');

        // 1) Tenta ler de $_POST (FormData)
        $idVaga = $_POST['id_vaga'] ?? null;
        $horaSaida = $_POST['hora_saida'] ?? null;
        $paymentMethod = $_POST['payment_method'] ?? null;
        $csrfToken = $_POST['csrf_token'] ?? null;

        // 2) Se não veio em $_POST, tenta JSON cru
        if (!$idVaga || !$horaSaida) {
            $raw = file_get_contents('php://input');
            if ($raw) {
                $data = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    $idVaga = $idVaga ?: ($data['id_vaga'] ?? null);
                    $horaSaida = $horaSaida ?: ($data['hora_saida'] ?? null);
                    $paymentMethod = $paymentMethod ?: ($data['payment_method'] ?? null);
                    $csrfToken = $csrfToken ?: ($data['csrf_token'] ?? null);
                }
            }
        }


        if (!$idVaga || !$horaSaida) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos (id ou hora faltando).']);
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $expectedCsrf = $_SESSION['vacancy_checkout_csrf'] ?? '';
        if (!is_string($csrfToken)
            || $expectedCsrf === ''
            || !hash_equals($expectedCsrf, $csrfToken)
        ) {
            http_response_code(419);
            echo json_encode([
                'success' => false,
                'message' => 'A sessão expirou. Recarregue a página e tente novamente.',
            ]);
            return;
        }

        try {
            $model = new ParkingService();

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

            $sanitizer = new InputSanitizer();
            $result = $model->finalizarVaga(
                (int) $idVaga,
                $sanitizer->text($horaSaida, 5),
                $sanitizer->text($paymentMethod, 10)
            );

            echo json_encode(['success' => true] + $result, JSON_THROW_ON_ERROR);
        } catch (DomainException $e) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
            ], JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Não foi possível finalizar a vaga.'
            ]);
        }
    }

    private function authorization(): AuthorizationService
    {
        return new AuthorizationService(IdentityContext::current());
    }

}

