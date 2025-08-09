<?php
namespace App\Controllers;

use Core\Controller;

class LoginController extends Controller
{
    public function index() {
        $this->setView('Login/login', [
            'title' => 'Login - SwiftlyPark',
            'message' => 'Por favor, faça login'
        ]);
    }

}
