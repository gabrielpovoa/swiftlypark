<?php

namespace App\models;

use Config\Database;
use PDO;
use PDOException;

class LoginModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    /**
     * Busca o usuário (login + dados básicos) pelo email
     * Retorna array com id_usuario, email, senha_hash, nome e photo
     */
    public function getUserByEmail($email)
    {
        $sql = "
            SELECT u.id_usuario, u.email, u.senha_hash, u.nome, u.photo
            FROM usuario u
            WHERE u.email = :email
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
