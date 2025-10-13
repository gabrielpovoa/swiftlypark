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

        public function getDailyIncome()
        {
            $sql = "
        SELECT SUM(valor) AS total_pago
        FROM transacoes t
        INNER JOIN vagas_preenchidas v ON t.id_vaga_preenchida = v.id_vaga_preenchida
        WHERE DATE(v.hora_entrada) = CURDATE()
           OR (v.hora_saida IS NOT NULL AND DATE(v.hora_saida) = CURDATE())
    ";
            $stmt = $this->db->query($sql);
            return $stmt->fetchColumn() ?: 0; // Retorna 0 se não houver transações
        }


        public function getLogEntry()
        {
            $sql = "
        SELECT 
            'entrada' AS tipo,
            DATE_FORMAT(hora_entrada, '%H:%i') AS hora,
            nome_cliente,
            placa,
            tipo_veiculo
        FROM vagas_preenchidas
        WHERE DATE(hora_entrada) = CURDATE()
        
        UNION ALL
        
        SELECT 
            'saida' AS tipo,
            DATE_FORMAT(hora_saida, '%H:%i') AS hora,
            nome_cliente,
            placa,
            tipo_veiculo
        FROM vagas_preenchidas
        WHERE hora_saida IS NOT NULL AND DATE(hora_saida) = CURDATE()
        
        ORDER BY hora DESC
    ";

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
