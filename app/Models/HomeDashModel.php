<?php

namespace App\Models;

use Config\Database;
use PDO;

class HomeDashModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    public function getIncomeByPeriod(string $startUtc, string $endUtc)
    {
        $sql = "
            SELECT COALESCE(SUM(valor), 0) AS total_pago
            FROM transacoes
            WHERE payment_date >= :start_utc
              AND payment_date < :end_utc
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'start_utc' => $startUtc,
            'end_utc' => $endUtc,
        ]);

        return $stmt->fetchColumn() ?: 0;
    }


    public function getLogEntriesByPeriod(string $startLocal, string $endLocal)
    {
        $sql = "
        SELECT 
            'entrada' AS tipo,
            DATE_FORMAT(hora_entrada, '%H:%i') AS hora,
            DATE_FORMAT(hora_entrada, '%d/%m') AS data,
            nome_cliente,
            placa,
            tipo_veiculo,
            hora_entrada AS evento_em
        FROM vagas_preenchidas
        WHERE hora_entrada >= :entry_start
          AND hora_entrada < :entry_end
        
        UNION ALL
        
        SELECT 
            'saida' AS tipo,
            DATE_FORMAT(hora_saida, '%H:%i') AS hora,
            DATE_FORMAT(hora_saida, '%d/%m') AS data,
            nome_cliente,
            placa,
            tipo_veiculo,
            hora_saida AS evento_em
        FROM vagas_preenchidas
        WHERE hora_saida >= :exit_start
          AND hora_saida < :exit_end
        
        ORDER BY evento_em DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'entry_start' => $startLocal,
            'entry_end' => $endLocal,
            'exit_start' => $startLocal,
            'exit_end' => $endLocal,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
