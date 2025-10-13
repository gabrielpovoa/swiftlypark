<?php
    namespace App\models;

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
            $sql = "SELECT DISTINCT DATE(hora_entrada) as value,
                       DATE_FORMAT(hora_entrada, '%d/%m/%Y') as label
                FROM vagas_preenchidas
                ORDER BY value DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getLogsByFilter($filter)
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
        WHERE DATE(hora_entrada) = ?
           OR (hora_saida IS NOT NULL AND DATE(hora_saida) = ?)
        ORDER BY hora_entrada ASC, hora_saida ASC
    ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$filter, $filter]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAllLogs()
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
        WHERE DATE(hora_entrada) = CURDATE()
           OR (hora_saida IS NOT NULL AND DATE(hora_saida) = CURDATE())
        ORDER BY hora_entrada ASC, hora_saida ASC
    ";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
