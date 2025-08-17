<?php

    namespace App\Models;

    use Config\Database;
    use PDO;
    use PDOException;

    class CreateAccModel
    {

        public function __construct()
        {
            $this->db = (new Database())->connect();
        }

        public function createUser($name, $email, $password)
        {
            try {
                // 1️⃣ Inserir no login
                $stmtLogin = $this->db->prepare("
                INSERT INTO login (email, senha) 
                VALUES (:email, :senha)
            ");
                $stmtLogin->execute([
                    'email' => $email,
                    'senha' => password_hash($password, PASSWORD_DEFAULT)
                ]);

                $idLogin = $this->db->lastInsertId();

                // 2️⃣ Inserir no usuario
                $stmtUsuario = $this->db->prepare("
                INSERT INTO usuario (id_login, nome, email, senha_hash) 
                VALUES (:id_login, :nome, :email, :senha_hash)
            ");
                $stmtUsuario->execute([
                    'id_login'   => $idLogin,
                    'nome'       => $name,
                    'email'      => $email,
                    'senha_hash' => password_hash($password, PASSWORD_DEFAULT)
                ]);

                return $this->db->lastInsertId();

            } catch (PDOException $e) {
                throw new \Exception("Erro ao criar usuário: " . $e->getMessage());
            }
        }

        public function verifyUser($email)
        {
            $sql = "SELECT id_usuario FROM usuario WHERE email = :email LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            return $stmt->fetch() !== false; // true se já existir
        }

    }
