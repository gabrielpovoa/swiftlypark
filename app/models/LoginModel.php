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

        public function getUserByEmail($email)
        {
            $sql = "
        SELECT l.id_login, l.email, l.senha, u.nome
        FROM login l
        INNER JOIN usuario u ON l.id_login = u.id_login
        WHERE l.email = :email
        LIMIT 1
    ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':email', $email, \PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetch(\PDO::FETCH_ASSOC);
        }



    }