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
            $sql = "SELECT DATE_FORMAT(hora_entrada, '%d/%m/%Y') as data,
            DATE_FORMAT(hora_entrada, '%H:%i') as hora_entrada,
            nome_cliente,
            placa,
            valor_pago
        FROM vagas_preenchidas
        WHERE DATE(hora_entrada) = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$filter]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAllLogs()
        {
            $sql = "SELECT DATE_FORMAT(hora_entrada, '%d/%m/%Y') as data,
                       DATE_FORMAT(hora_entrada, '%H:%i') as hora_entrada,
                       nome_cliente,
                       placa,
                       valor_pago
                FROM vagas_preenchidas
                ORDER BY hora_entrada DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
