<?php

    namespace App\Parking\Infrastructure;

    use App\Context\IdentityContext;
    use App\Parking\Domain\BillingMode;
    use App\Parking\Domain\VacancyStatus;
    use App\Parking\Domain\VehicleType;
    use App\Context\TenantContext;
    use App\Exceptions\TenantNotSetException;
    use App\Billing\Infrastructure\PricingRepository;
    use App\Billing\Infrastructure\MonthlyContractRepository;
    use App\Billing\Application\PriceCalculator;
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

    final class PdoParkingGateway
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
                  AND company_id = :company_id
                GROUP BY categoria";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['company_id' => $this->companyId()]);
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
                WHERE categoria = :cat
                  AND status = 'livre'
                  AND company_id = :company_id
                LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'cat' => $categoria,
                'company_id' => $this->companyId(),
            ]);
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
            $sql = "SELECT * FROM vagas_disponiveis
                WHERE id_vaga = :id
                  AND company_id = :company_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id' => $idVaga,
                'company_id' => $this->companyId(),
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        public function isMonthlyCompany(): bool
        {
            return (new PricingRepository($this->db))->isMonthlyCompany($this->companyId());
        }

        public function ocuparVaga(
            int $idVaga,
            string $horaEntrada,
            string $ownerName,
            string $phone,
            string $plate,
            string $tipoVeiculo
        ): int
        {
            $tipoVeiculo = VehicleType::fromInput($tipoVeiculo)->value;
            (new AuthorizationService(IdentityContext::current()))
                ->check('vehicle.checkin');

            return $this->transactions->run(function () use (
                $idVaga,
                $horaEntrada,
                $ownerName,
                $phone,
                $plate,
                $tipoVeiculo
            ) {
                $hasMonthlyContract = $this->isMonthlyCompany()
                    && (new MonthlyContractRepository($this->db))->activeForVehicle(
                        $this->companyId(),
                        $plate,
                        $tipoVeiculo
                    ) !== null;

                $idVagaPreenchida = $this->insertVagaPreenchida(
                    $idVaga,
                    $horaEntrada,
                    null,
                    $ownerName,
                    $phone,
                    $plate,
                    0.0,
                    $tipoVeiculo,
                    $hasMonthlyContract ? BillingMode::Monthly->value : BillingMode::Rotating->value
                );
                $this->updateVagaStatus($idVaga, VacancyStatus::Reserved->value);

                return $idVagaPreenchida;
            });
        }

        public function checkIfVehicleIsParked(string $plate)
        {
            $sql = "SELECT id_vaga FROM vagas_preenchidas
                WHERE placa = :plate
                  AND hora_saida IS NULL
                  AND company_id = :company_id
                LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':plate', strtoupper(trim($plate)));
            $stmt->bindValue(':company_id', $this->companyId(), PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch();
        }


        private function insertVagaPreenchida($idVaga, $horaEntrada, $horaSaida, $ownerName, $phone, $plate, $paidAmount, $tipoVeiculo, $billingModel)
        {
            $userId = IdentityContext::current()->userId();
            $sql = "INSERT INTO vagas_preenchidas 
        (company_id, id_vaga, hora_entrada, hora_saida, nome_cliente, telefone, placa, valor_pago, tipo_veiculo, billing_model, created_by, updated_by)
        VALUES 
        (:company_id, :id_vaga, :hora_entrada, :hora_saida, :nome, :telefone, :placa, :valor_pago, :tipo_veiculo, :billing_model, :created_by, :updated_by)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'company_id' => $this->companyId(),
                'id_vaga' => $idVaga,
                'hora_entrada' => $horaEntrada,
                'hora_saida' => $horaSaida,
                'nome' => $ownerName,
                'telefone' => $phone,
                'placa' => strtoupper($plate),
                'valor_pago' => (float)$paidAmount,
                'tipo_veiculo' => $tipoVeiculo,
                'billing_model' => $billingModel,
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


        private function insertTransacao(
            int $idVagaPreenchida,
            float $valorPago,
            string $paymentMethod
        ): int
        {
            $userId = IdentityContext::current()->userId();
            $sql = "INSERT INTO transacoes 
                (company_id, id_vaga_preenchida, valor, payment_method, data_transacao, payment_date, created_by, updated_by)
                VALUES 
                (:company_id, :id_vaga_preenchida, :valor, :payment_method, UTC_TIMESTAMP(), UTC_TIMESTAMP(), :created_by, :updated_by)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'company_id' => $this->companyId(),
                'id_vaga_preenchida' => $idVagaPreenchida,
                'valor' => (float)$valorPago,
                'payment_method' => $paymentMethod,
                'created_by' => $userId,
                'updated_by' => $userId
            ]);

            $id = (int) $this->db->lastInsertId();
            $this->audit->created(
                'transacoes',
                $id,
                fn (): array => $this->findTransaction($id)
            );

            return $id;
        }

        private function updateVagaStatus($idVaga, $status)
        {
            $oldValues = $this->findAvailableVacancyForUpdate((int) $idVaga);
            $sql = "UPDATE vagas_disponiveis
                    SET status = :status, updated_by = :updated_by
                    WHERE id_vaga = :id
                      AND company_id = :company_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'status' => $status,
                'updated_by' => IdentityContext::current()->userId(),
                'id' => $idVaga,
                'company_id' => $this->companyId(),
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
            $sql = "SELECT v.*, p.placa, p.hora_entrada, p.id_vaga_preenchida, p.billing_model
        FROM vagas_disponiveis v
        LEFT JOIN vagas_preenchidas p 
          ON v.id_vaga = p.id_vaga
         AND p.company_id = v.company_id
         AND p.hora_saida IS NULL
        WHERE v.company_id = :company_id";

            $params = ['company_id' => $this->companyId()];

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

        public function finalizarVaga(
            int $idVaga,
            string $horaSaida,
            ?string $paymentMethod
        ): array
        {
            (new AuthorizationService(IdentityContext::current()))
                ->check('vehicle.checkout');

            if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $horaSaida) !== 1) {
                throw new Exception('A hora de saída é inválida.');
            }

            $companyId = $this->companyId();
            $pricingRepository = new PricingRepository($this->db);

            return $this->transactions->run(function () use (
                $companyId,
                $idVaga,
                $horaSaida,
                $paymentMethod,
                $pricingRepository
            ): array {
                $this->findAvailableVacancyForUpdate($idVaga);
                $vagaPreenchida = $this->findActiveFilledVacancyForUpdate($idVaga);
                if ($vagaPreenchida === []) {
                    throw new Exception('Não foi encontrada uma vaga ativa para finalizar.');
                }

                $horaEntrada = new DateTime((string) $vagaPreenchida['hora_entrada']);
                $horaSaidaInput = new DateTime($horaEntrada->format('Y-m-d'));
                [$hora, $minuto] = explode(':', $horaSaida);
                $horaSaidaInput->setTime((int) $hora, (int) $minuto);
                if ($horaSaidaInput < $horaEntrada) {
                    $horaSaidaInput->modify('+1 day');
                }

                $durationSeconds = $horaSaidaInput->getTimestamp() - $horaEntrada->getTimestamp();
                $durationMinutes = (int) ceil($durationSeconds / 60);
                $hours = intdiv($durationSeconds, 3600);
                $minutes = intdiv($durationSeconds % 3600, 60);
                $seconds = $durationSeconds % 60;
                $tempoTotal = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                $isMonthly = ($vagaPreenchida['billing_model'] ?? 'ROTATING') === 'MONTHLY';
                $calculation = null;
                $transactionId = null;

                if (!$isMonthly) {
                    $normalizedPaymentMethod = strtoupper(trim((string) $paymentMethod));
                    if (!in_array($normalizedPaymentMethod, ['PIX', 'CARD', 'CASH'], true)) {
                        throw new Exception('Selecione uma forma de pagamento válida.');
                    }

                    $calculation = (new PriceCalculator($pricingRepository))->calculateDetails(
                        $companyId,
                        (string) $vagaPreenchida['tipo_veiculo'],
                        $durationMinutes
                    );
                    $transactionId = $this->insertTransacao(
                        (int) $vagaPreenchida['id_vaga_preenchida'],
                        (float) $calculation['total'],
                        $normalizedPaymentMethod
                    );
                }

                $lockedFilledVacancy = $this->findFilledVacancyForUpdate(
                    (int) $vagaPreenchida['id_vaga_preenchida']
                );
                $sqlUpdate = "UPDATE vagas_preenchidas
                      SET
                          hora_saida = :hora_saida,
                          tempo_total = :tempo_total,
                          valor_pago = :valor_pago,
                          updated_by = :updated_by
                      WHERE id_vaga_preenchida = :id
                        AND company_id = :company_id";

                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    'hora_saida' => $horaSaidaInput->format('Y-m-d H:i:s'),
                    'tempo_total' => $tempoTotal,
                    'valor_pago' => $calculation['total'] ?? 0,
                    'updated_by' => IdentityContext::current()->userId(),
                    'id' => $vagaPreenchida['id_vaga_preenchida'],
                    'company_id' => $this->companyId(),
                ]);

                $this->audit->updated(
                    'vagas_preenchidas',
                    (int) $vagaPreenchida['id_vaga_preenchida'],
                    $lockedFilledVacancy,
                    fn (): array => $this->findFilledVacancy(
                        (int) $vagaPreenchida['id_vaga_preenchida']
                    )
                );

                $this->updateVagaStatus($idVaga, 'livre');

                (new AuditService(
                    new AuditLogRepository($this->db),
                    IdentityContext::current()
                ))->log(AuditService::UPDATE, [
                    'entity' => 'pricing_checkout',
                    'entity_id' => (int) $vagaPreenchida['id_vaga_preenchida'],
                    'new_values' => [
                        'operator_user_id' => IdentityContext::current()->userId(),
                        'company_id' => $companyId,
                        'entry_at' => $horaEntrada->format('Y-m-d H:i:s'),
                        'exit_at' => $horaSaidaInput->format('Y-m-d H:i:s'),
                        'billing_model' => $isMonthly ? 'MONTHLY' : 'ROTATING',
                        'transaction_id' => $transactionId,
                        'calculation' => $calculation,
                    ],
                ]);

                return [
                    'monthly' => $isMonthly,
                    'duration_minutes' => $durationMinutes,
                    'amount' => $calculation['total'] ?? 0.0,
                    'transaction_id' => $transactionId,
                ];
            });
        }

        private function findActiveFilledVacancyForUpdate(int $idVaga): array
        {
            $statement = $this->db->prepare(
                'SELECT * FROM vagas_preenchidas
                 WHERE id_vaga = :id_vaga
                   AND hora_saida IS NULL
                   AND company_id = :company_id
                 ORDER BY id_vaga_preenchida DESC
                 LIMIT 1
                 FOR UPDATE'
            );
            $statement->execute([
                'id_vaga' => $idVaga,
                'company_id' => $this->companyId(),
            ]);

            return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        private function findAvailableVacancyForUpdate(int $id): array
        {
            $statement = $this->db->prepare(
                'SELECT * FROM vagas_disponiveis
                 WHERE id_vaga = :id
                   AND company_id = :company_id
                 FOR UPDATE'
            );
            $statement->execute([
                'id' => $id,
                'company_id' => $this->companyId(),
            ]);

            return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        private function findFilledVacancyForUpdate(int $id): array
        {
            $statement = $this->db->prepare(
                'SELECT * FROM vagas_preenchidas
                 WHERE id_vaga_preenchida = :id
                   AND company_id = :company_id
                 FOR UPDATE'
            );
            $statement->execute([
                'id' => $id,
                'company_id' => $this->companyId(),
            ]);

            return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        private function findFilledVacancy(int $id): array
        {
            $statement = $this->db->prepare(
                'SELECT * FROM vagas_preenchidas
                 WHERE id_vaga_preenchida = :id'
                . ' AND company_id = :company_id'
            );
            $statement->execute([
                'id' => $id,
                'company_id' => $this->companyId(),
            ]);

            return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        private function findTransaction(int $id): array
        {
            $statement = $this->db->prepare(
                'SELECT * FROM transacoes
                 WHERE id_transacao = :id'
                . ' AND company_id = :company_id'
            );
            $statement->execute([
                'id' => $id,
                'company_id' => $this->companyId(),
            ]);

            return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        private function companyId(): int
        {
            $companyId = TenantContext::instance()->getCompanyId();
            if ($companyId === null) {
                throw new TenantNotSetException();
            }

            return $companyId;
        }

    }
