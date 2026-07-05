<?php
namespace App\Controllers;

use App\Authorization\Repositories\RbacRepository;
use App\Authorization\Services\RolePermissionResolver;
use Core\Controller;
use App\Models\LoginModel;
use Config\Database;

class LoginController extends Controller
{
    // Mostra a página de login
    public function index() {
        $this->setView('Login/login', [
            'title' => 'Login - SwiftlyPark',
            'message' => 'Por favor, faça login'
        ], false);
    }

    // Autentica o usuário
    public function authenticate()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $model = new LoginModel();
        $user = $model->getUserByEmail($email); // Busca login + usuário

        if ($user && password_verify($password, $user['senha_hash'])) {
            // Login bem-sucedido
            session_regenerate_id(true);

            $_SESSION['user_id']    = $user['id_usuario'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name']  = $user['nome'];
            $_SESSION['user_photo'] = $user['photo'] ?? null;

            $connection = (new Database())->connect();
            $authorization = (new RolePermissionResolver(
                new RbacRepository($connection)
            ))->resolve((int) $user['id_usuario']);
            $_SESSION['permissions'] = $authorization->permissions();
            $_SESSION['role_slugs'] = $authorization->roleSlugs();
            $_SESSION['role_metadata'] = $authorization
                ->roleMetadata()
                ->toArray();

            header('Location: /');
            exit;
        } else {
            // Login inválido
            $error = $user ? 'Senha incorreta.' : 'Email não encontrado.';
            $this->setView('Login/login', [
                'title' => 'Login - SwiftlyPark',
                'error' => $error
            ], false);
        }
    }

    // Logout do usuário
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Limpa todas as variáveis de sessão
        $_SESSION = [];
        session_destroy();

        header('Location: /login');
        exit;
    }
}
