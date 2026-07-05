<?php

    namespace App\Models;

    use App\Context\IdentityContext;
    use App\Repositories\AuditLogRepository;
    use App\Repositories\Decorators\TransactionalAuditDecorator;
    use App\Services\AuditService;
    use App\Services\AuthorizationService;
    use App\Transactions\TransactionManager;
    use Config\Database;
    use DateTime;
    use Exception;
    use PDO;
    use PDOException;

    class VacancyModel
    {
        private PDO $db;
        private TransactionalAuditDecorator $audit;
        private TransactionManager $transactions;

        public function __construct()
        {
            $this->db = (new Database())->connect();
            $this->audit = new TransactionalAuditDecorator(
                new AuditService(
                    new AuditLogRepository($this->db),
                    IdentityContext::current()
                )
            );
            $this->transactions = new TransactionManager($this->db);
        }

        public function getAvailableCounts()
        {
            $sql = "SELECT categoria, COUNT(*) as total
                FROM vagas_disponiveis
                WHERE status = 'livre'
                GROUP BY categoria";

            $stmt = $this->db->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $counts = [];
            foreach ($rows as $row) {
                $counts[$row['categoria']] = (int)$row['total'];
            }
            return $counts;
        }

        public function getFreeVagaByCategory($categoria)
        {
            $sql = "SELECT * FROM vagas_disponiveis 
                WHERE categoria = :cat AND status = 'livre'
                LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['cat' => $categoria]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        public function getVacancyByType($categoria)
        {
            $detalhes = [
                'carro' => [
                    'title' => 'Vaga para Carro',
                    'description' => 'Estacione seu carro com segurança.',
                    'discount' => 'Desconto especial para mensalistas.',
                    'image' => '/images/car.png'
                ],
                'moto' => [
                    'title' => 'Vaga para Moto',
                    'description' => 'Vagas exclusivas para motocicletas.',
                    'discount' => '',
                    'image' => '/images/moto.png'
                ],
                'caminhao' => [
                    'title' => 'Vaga para Caminhão',
                    'description' => 'Área especial para caminhões.',
                    'discount' => '',
                    'image' => '/images/truck.png'
                ],
                'app' => [
                    'title' => 'Vaga para Motoristas de Aplicativos',
                    'description' => 'Espaço reservado para Uber, 99Pop e Indrive.',
                    'discount' => 'Preços especiais para motoristas de app.',
                    'image' => '/images/uber.png'
                ]
            ];

            return $detalhes[$categoria] ?? $detalhes['carro'];
        }

        public function getVagaById($idVaga)
        {
            $sql = "SELECT * FROM vagas_disponiveis WHERE id_vaga = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $idVaga]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        /**
         * Reserva a vaga, insere em vagas_preenchidas e transacoes e marca como preenchida
         */
        public function ocuparVagaComPagamento($idVaga, $horaEntrada, $horaSaida, $ownerName, $phone, $plate, $valorPago, $tipoVeiculo)
        {
            (new AuthorizationService(IdentityContext::current()))
                ->check('vehicle.checkin');

            return $this->transactions->run(function () use (
                $idVaga,
                $horaEntrada,
                $horaSaida,
                $ownerName,
                $phone,
                $plate,
                $valorPago,
                $tipoVeiculo
            ) {
                $idVagaPreenchida = $this->insertVagaPreenchida($idVaga, $horaEntrada, $horaSaida, $ownerName, $phone, $plate, $valorPago, $tipoVeiculo);
                $this->insertTransacao($idVagaPreenchida, $valorPago);
                $this->updateVagaStatus($idVaga, 'reservada');

                return $idVagaPreenchida;
            });
        }

        public function checkIfVehicleIsParked(string $plate)
        {
            $sql = "SELECT id_vaga FROM vagas_preenchidas WHERE placa = :plate AND hora_saida IS NULL LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':plate', strtoupper(trim($plate)));
            $stmt->execute();

            return $stmt->fetch();
        }


        private function insertVagaPreenchida($idVaga, $horaEntrada, $horaSaida, $ownerName, $phone, $plate, $paidAmount, $tipoVeiculo)
        {
            $userId = IdentityContext::current()->userId();
            $sql = "INSERT INTO vagas_preenchidas 
        (id_vaga, hora_entrada, hora_saida, nome_cliente, telefone, placa, valor_pago, tipo_veiculo, created_by, updated_by)
        VALUES 
        (:id_vaga, :hora_entrada, :hora_saida, :nome, :telefone, :placa, :valor_pago, :tipo_veiculo, :created_by, :updated_by)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id_vaga' => $idVaga,
                'hora_entrada' => $horaEntrada,
                'hora_saida' => $horaSaida,
                'nome' => $ownerName,
                'telefone' => $phone,
                'placa' => strtoupper($plate),
                'valor_pago' => (float)$paidAmount,
                'tipo_veiculo' => $tipoVeiculo,
                'created_by' => $userId,
                'updated_by' => $userId
            ]);

            $id = (int) $this->db->lastInsertId();
            $this->audit->created(
                'vagas_preenchidas',
                $id,
                fn (): array => $this->findFilledVacancy($id)
            );

            return $id;
        }


        private function insertTransacao($idVagaPreenchida, $valorPago)
        {
            $userId = IdentityContext::current()->userId();
            $sql = "INSERT INTO transacoes 
                (id_vaga_preenchida, valor, data_transacao, created_by, updated_by)
                VALUES 
                (:id_vaga_preenchida, :valor, NOW(), :created_by, :updated_by)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id_vaga_preenchida' => $idVagaPreenchida,
                'valor' => (float)$valorPago,
                'created_by' => $userId,
                'updated_by' => $userId
            ]);

            $id = (int) $this->db->lastInsertId();
            $this->audit->created(
                'transacoes',
                $id,
                fn (): array => $this->findTransaction($id)
            );
        }

        private function updateVagaStatus($idVaga, $status)
        {
            $oldValues = $this->findAvailableVacancyForUpdate((int) $idVaga);
            $sql = "UPDATE vagas_disponiveis
                    SET status = :status, updated_by = :updated_by
                    WHERE id_vaga = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'status' => $status,
                'updated_by' => IdentityContext::current()->userId(),
                'id' => $idVaga
            ]);

            $this->audit->updated(
                'vagas_disponiveis',
                (int) $idVaga,
                $oldValues,
                fn (): array => $this->getVagaById($idVaga)
            );
        }

        public function getVagasFiltradas($categoria = null, $placa = null)
        {
            $sql = "SELECT v.*, p.placa, p.hora_entrada, p.id_vaga_preenchida
        FROM vagas_disponiveis v
        LEFT JOIN vagas_preenchidas p 
          ON v.id_vaga = p.id_vaga AND p.hora_saida IS NULL
        WHERE 1=1";

            $params = [];

// Filtrar por categoria
            if ($categoria && $categoria !== 'all') {
                $sql .= " AND v.categoria = :categoria";
                $params['categoria'] = $categoria;
            }

// Filtrar por placa apenas na entrada ativa
            if ($placa) {
                $sql .= " AND p.placa LIKE :placa";
                $params['placa'] = "%{$placa}%";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        }

        public function finalizarVaga($idVaga, $horaSaida)
        {
            (new AuthorizationService(IdentityContext::current()))
                ->check('vehicle.checkout');

            // Buscar a vaga ativa (sem hora de saída)
            $sql = "SELECT * FROM vagas_preenchidas
            WHERE id_vaga = :id
              AND hora_saida IS NULL
            ORDER BY id_vaga_preenchida DESC
            LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id' => $idVaga
            ]);

            $vagaPreenchida = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$vagaPreenchida) {
                throw new Exception("Não foi encontrada uma vaga ativa para finalizar.");
            }

            // Hora de entrada real salva no banco
            $horaEntrada = new DateTime($vagaPreenchida['hora_entrada']);

            // Hora de saída enviada pelo SweetAlert (HH:mm)
            $horaSaidaInput = new DateTime(date('Y-m-d'));
            [$hora, $minuto] = explode(':', $horaSaida);
            $horaSaidaInput->setTime((int)$hora, (int)$minuto, 0);

            // Se saída menor que entrada → passou da meia-noite
            if ($horaSaidaInput < $horaEntrada) {
                $horaSaidaInput->modify('+1 day');
            }

            $intervalo = $horaEntrada->diff($horaSaidaInput);

            /**
             * CORREÇÃO PRINCIPAL:
             * MySQL espera TIME e não texto
             *
             * Exemplo válido:
             * 00:15:00
             * 02:30:00
             */
            $tempoTotal = sprintf(
                '%02d:%02d:%02d',
                ($intervalo->days * 24) + $intervalo->h,
                $intervalo->i,
                $intervalo->s
            );

            $this->transactions->run(function () use (
                $vagaPreenchida,
                $horaSaidaInput,
                $tempoTotal,
                $idVaga
            ) {
                $lockedFilledVacancy = $this->findFilledVacancyForUpdate(
                    (int) $vagaPreenchida['id_vaga_preenchida']
                );
                // Atualiza a vaga preenchida corretamente
                $sqlUpdate = "UPDATE vagas_preenchidas
                      SET
                          hora_saida = :hora_saida,
                          tempo_total = :tempo_total,
                          updated_by = :updated_by
                      WHERE id_vaga_preenchida = :id";

                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    'hora_saida' => $horaSaidaInput->format('Y-m-d H:i:s'),
                    'tempo_total' => $tempoTotal,
                    'updated_by' => IdentityContext::current()->userId(),
                    'id' => $vagaPreenchida['id_vaga_preenchida']
                ]);

                $this->audit->updated(
                    'vagas_preenchidas',
                    (int) $vagaPreenchida['id_vaga_preenchida'],
                    $lockedFilledVacancy,
                    fn (): array => $this->findFilledVacancy(
                        (int) $vagaPreenchida['id_vaga_preenchida']
                    )
                );

                // Libera a vaga novamente
                $this->updateVagaStatus($idVaga, 'livre');
            });
        }

        private function findAvailableVacancyForUpdate(int $id): array
        {
            $statement = $this->db->prepare(
                'SELECT * FROM vagas_disponiveis
                 WHERE id_vaga = :id
                 FOR UPDATE'
            );
            $statement->execute(['id' => $id]);

            return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        private function findFilledVacancyForUpdate(int $id): array
        {
            $statement = $this->db->prepare(
                'SELECT * FROM vagas_preenchidas
                 WHERE id_vaga_preenchida = :id
                 FOR UPDATE'
            );
            $statement->execute(['id' => $id]);

            return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        private function findFilledVacancy(int $id): array
        {
            $statement = $this->db->prepare(
                'SELECT * FROM vagas_preenchidas
                 WHERE id_vaga_preenchida = :id'
            );
            $statement->execute(['id' => $id]);

            return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        private function findTransaction(int $id): array
        {
            $statement = $this->db->prepare(
                'SELECT * FROM transacoes
                 WHERE id_transacao = :id'
            );
            $statement->execute(['id' => $id]);

            return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        }

    }
