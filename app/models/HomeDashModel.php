<?php

namespace App\Models;

use Config\Database;
use PDO;
use PDOException;


class HomeDashModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    public function getDailyIncome()
    {
        $sql = "SELECT SUM(valor) as total_pago FROM transacoes WHERE DATE(data_transacao) = CURDATE();";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getLogEntry()
    {
        $sql = "SELECT DATE_FORMAT(hora_entrada, '%H:%i') as hora_entrada, nome_cliente, placa FROM vagas_preenchidas";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}