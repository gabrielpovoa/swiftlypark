<?php
namespace App\Controllers;

use Core\Controller;
use Config\Database;

class HomeController extends Controller {
    public function index() {
        $db = new Database();
        $conn = $db->connect();
        $this->setView('Home/home', [
            'title' => 'Página Inicial - SwiftlyPark',
            'message' => 'Conexão OK!'
        ]);
    }
}
