<?php

namespace App\Models;

use Config\Database;
use PDO;
use PDOException;

class CreateAccModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    /**
     * Cria usuário — insere em login e usuario usando transação.
     * Retorna id_usuario criado (int) ou lança Exception em erro.
     */
    public function createUser($name, $email, $password)
    {
        try {
            // Verifica se já existe
            if ($this->verifyUser($email)) {
                throw new \Exception("Já existe um usuário com esse e-mail.");
            }

            $this->db->beginTransaction();

            $hash = password_hash($password, PASSWORD_DEFAULT);

            // 1) Inserir em login
            $stmtLogin = $this->db->prepare("
                INSERT INTO login (email, senha) 
                VALUES (:email, :senha)
            ");
            $stmtLogin->execute([
                ':email' => $email,
                ':senha' => $hash
            ]);

            $idLogin = $this->db->lastInsertId();

            // 2) Inserir em usuario
            $stmtUsuario = $this->db->prepare("
                INSERT INTO usuario (id_login, nome, email, senha_hash) 
                VALUES (:id_login, :nome, :email, :senha_hash)
            ");
            $stmtUsuario->execute([
                ':id_login'   => $idLogin,
                ':nome'       => $name,
                ':email'      => $email,
                ':senha_hash' => $hash
            ]);

            $idUsuario = $this->db->lastInsertId();

            $this->db->commit();

            return (int)$idUsuario;

        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new \Exception("Erro ao criar usuário (DB): " . $e->getMessage());
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function verifyUser($email)
    {
        $sql = "SELECT id_usuario FROM usuario WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false; // true se já existir
    }
}
