<?php

    namespace App\Models;

    use Config\Database;
    use PDO;

    class CreateAccModel
    {
        private $db;

        public function __construct()
        {
            $this->db = (new Database())->connect();
        }

        public function createUser($name, $email, $avatar, $password)
        {
            $sql = "INSERT INTO usuario (nome, email, foto_perfil, senha_hash)
                VALUES (:nome, :email, :foto_perfil, :senha_hash)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'nome'        => $name,
                'email'       => $email,
                'foto_perfil' => $avatar,
                'senha_hash'  => password_hash($password, PASSWORD_DEFAULT)
            ]);

            return $this->db->lastInsertId();
        }
    }
