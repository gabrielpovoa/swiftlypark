<?php
namespace App\Models;

use Config\Database;
use PDO;

class LogsModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    public function getFilters()
    {
        $sql = "
            SELECT month_value AS value,
                   DATE_FORMAT(CONCAT(month_value, '-01'), '%m/%Y') AS label
            FROM (
                SELECT DATE_FORMAT(hora_entrada, '%Y-%m') AS month_value
                FROM vagas_preenchidas

                UNION

                SELECT DATE_FORMAT(hora_saida, '%Y-%m') AS month_value
                FROM vagas_preenchidas
                WHERE hora_saida IS NOT NULL
            ) AS available_months
            ORDER BY month_value DESC
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLogsByPeriod(string $startLocal, string $endLocal)
    {
        $sql = "
        SELECT 
            DATE_FORMAT(hora_entrada, '%d/%m/%Y') AS data,
            DATE_FORMAT(hora_entrada, '%H:%i') AS hora_entrada,
            DATE_FORMAT(hora_saida, '%H:%i') AS hora_saida,
            nome_cliente,
            placa,
            valor_pago
        FROM vagas_preenchidas
        WHERE (hora_entrada >= :entry_start AND hora_entrada < :entry_end)
           OR (hora_saida >= :exit_start AND hora_saida < :exit_end)
        ORDER BY hora_entrada ASC, hora_saida ASC
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
