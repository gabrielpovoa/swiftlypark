<?php
namespace App\Controllers;

use Core\Controller;
use Config\Database;

class HomeController extends Controller {
    public function index() {
        $db = new Database();
        $conn = $db->connect(); // Testa conexão
        $this->setView('home', ['message' => 'Conexão OK!']);
    }
}
