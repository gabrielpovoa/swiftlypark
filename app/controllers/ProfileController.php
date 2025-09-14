<?php

    namespace App\Controllers;

    use Core\Controller;
    use App\Models\User;

    class ProfileController extends Controller
    {
        public function index()
        {
            $this->setView('Profile/profile', [
                'title' => 'Perfil - SwiftlyPark'
            ]);
        }

        public function changePassword()
        {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                $senhaAtual = $_POST['current_password'] ?? null;
                $novaSenha  = $_POST['new_password'] ?? null;

                if (!$senhaAtual || !$novaSenha) {
                    $this->setView('Profile/profile', [
                        'title'   => 'Perfil - SwiftlyPark',
                        'message' => 'Preencha todos os campos.'
                    ]);
                    return;
                }

                $userModel = new User();
                $resultado = $userModel->changePassword($_SESSION['user_id'], $senhaAtual, $novaSenha);

                $this->setView('Profile/profile', [
                    'title'   => 'Perfil - SwiftlyPark',
                    'message' => $resultado['message']
                ]);
                return;
            }

            $this->setView('Profile/profile', [
                'title' => 'Perfil - SwiftlyPark'
            ]);
        }
    }
