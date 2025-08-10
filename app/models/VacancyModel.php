<?php

namespace App\Models;

use Config\Database;
use PDO;
use PDOException;

class VacancyModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
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
            $counts[$row['categoria']] = (int) $row['total'];
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

    public function insertVagaPreenchida($idVaga, $horaEntrada, $horaSaida, $ownerName, $phone, $plate, $paidAmount)
    {
        $sql = "INSERT INTO vagas_preenchidas 
                (id_vaga, hora_entrada, hora_saida, nome_cliente, telefone, placa, valor_pago)
                VALUES 
                (:id_vaga, :hora_entrada, :hora_saida, :nome, :telefone, :placa, :valor_pago)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id_vaga'      => $idVaga,
            'hora_entrada' => $horaEntrada,
            'hora_saida'   => $horaSaida,
            'nome'         => $ownerName,
            'telefone'     => $phone,
            'placa'        => strtoupper($plate),
            'valor_pago'   => (float)$paidAmount
        ]);
        return $this->db->lastInsertId();
    }

    public function insertTransacao($idVagaPreenchida, $valorPago)
    {
        $sql = "INSERT INTO transacoes 
                (id_vaga_preenchida, valor, data_transacao)
                VALUES 
                (:id_vaga_preenchida, :valor, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id_vaga_preenchida' => $idVagaPreenchida,
            'valor'              => (float)$valorPago
        ]);
    }

    public function updateVagaStatus($idVaga, $status)
    {
        $sql = "UPDATE vagas_disponiveis SET status = :status WHERE id_vaga = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'status' => $status,
            'id' => $idVaga
        ]);
    }

    /**
     * Reserva a vaga, insere em vagas_preenchidas e transacoes e marca como preenchida
     */
    public function ocuparVagaComPagamento($idVaga, $horaEntrada, $horaSaida, $ownerName, $phone, $plate, $valorPago)
    {
        try {
            $this->db->beginTransaction();

            $idVagaPreenchida = $this->insertVagaPreenchida($idVaga, $horaEntrada, $horaSaida, $ownerName, $phone, $plate, $valorPago);
            $this->insertTransacao($idVagaPreenchida, $valorPago);
            $this->updateVagaStatus($idVaga, 'reservada');

            $this->db->commit();
            return $idVagaPreenchida;

        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
