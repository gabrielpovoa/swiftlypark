<?php
namespace Config;

use PDO;
use PDOException;

class Database {
    // No Docker, o host deve ser o nome do serviço definido no YAML (ex: 'db')
    // No Laragon, continua sendo 'localhost'
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // Lógica inteligente: Se existir a variável de ambiente do Docker, use-a.
        // Caso contrário, usa as configurações padrão do Laragon.
        $this->host     = getenv('DB_HOST')     ?: "localhost";
        $this->db_name  = getenv('DB_DATABASE') ?: "parking";
        $this->username = getenv('DB_USERNAME') ?: "root";
        $this->password = getenv('DB_PASSWORD') ?: "";
    }

    public function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            return $this->conn;
        } catch (PDOException $e) {
            die("Erro de conexão: " . $e->getMessage());
        }
    }
}
