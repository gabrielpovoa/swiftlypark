<?php

namespace App\models;

use Config\Database;
use PDO;
use PDOException;

class PasswordRecovery
{
    /**
     * Propriedade declarada para evitar o erro de 'Deprecated dynamic property'
     * @var PDO
     */
    private PDO $db;

    public function __construct()
    {
        // Instancia a conexão e atribui à propriedade protegida
        $this->db = (new Database())->connect();
    }

    public function getUserByEmail($email)
    {
        $sql = "
            SELECT u.id_usuario, u.email, u.senha_hash, u.nome
            FROM usuario u
            WHERE u.email = :email
            LIMIT 1
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Logar o erro se necessário
            return false;
        }
    }

    public function updatePassword($userId, $newPassword)
    {
        // O PASSWORD_DEFAULT hoje usa o algoritmo BCRYPT, altamente seguro
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $sql = "UPDATE usuario SET senha_hash = :senha WHERE id_usuario = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':senha', $hash, PDO::PARAM_STR);
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}