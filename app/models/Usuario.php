<?php

namespace models;

use Config\Database;
use PDO;
use PDOException;
class Usuario
{
    public function __construct()
    {
        $this->db = (new Database())->connect();
    }
}