<?php
namespace App\Controllers;

use Core\Controller;
use App\models\LoginModel;

class LoginController extends Controller
{
    public function index() {
        $this->setView('Login/login', [
            'title' => 'Login - SwiftlyPark',
            'message' => 'Por favor, faça login'
        ], false); // false = sem layout, só a view pura
    }
    public function authenticate()
    {
        $model = new LoginModel();
        $user = $model->getUserLogged();
        var_dump($user);
    }

}
