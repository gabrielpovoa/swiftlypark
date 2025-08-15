<?php

    namespace App\models;

    use Config\Database;
    use PDO;
    use PDOException;

    class LoginModel
    {
        public function __construct()
        {
            $this->db = (new Database())->connect();
        }

        public function getUserLogged()
        {
            $sql = "SELECT * FROM login";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }