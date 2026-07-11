<?php
namespace App\Controllers;

use App\Authorization\Repositories\RbacRepository;
use App\Authorization\Services\RolePermissionResolver;
use App\Repositories\TenantRepository;
use Core\Controller;
use App\Models\LoginModel;
use Config\Database;

class LoginController extends Controller
{
    // Mostra a página de login
    public function index() {
        $this->setView('Login/login', [
            'title' => 'Login - SwiftlyPark',
            'message' => 'Por favor, faça login',
            'error' => isset($_GET['revoked'])
                ? 'Seu acesso foi revogado. Entre em contato com o administrador do sistema.'
                : null,
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

        if (
            $user
            && password_verify($password, $user['senha_hash'])
            && $user['deleted_at'] !== null
        ) {
            $this->setView('Login/login', [
                'title' => 'Login - SwiftlyPark',
                'error' => 'Seu acesso foi revogado. Entre em contato com o administrador do sistema.'
            ], false);

            return;
        }

        if ($user && password_verify($password, $user['senha_hash'])) {
            if ((int) ($user['password_reset_required'] ?? 0) === 1) {
                session_regenerate_id(true);
                $_SESSION['force_password_reset_user_id'] = (int) $user['id_usuario'];
                $_SESSION['force_password_reset_email'] = $user['email'];
                header('Location: /login/password-required');
                exit;
            }

            // Login bem-sucedido
            session_regenerate_id(true);

            $_SESSION['user_id']    = $user['id_usuario'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name']  = $user['nome'];
            $_SESSION['user_photo'] = $user['photo'] ?? null;

            $connection = (new Database())->connect();
            $company = (new TenantRepository($connection))
                ->findFirstCompanyForUser((int) $user['id_usuario']);
            if ($company !== null) {
                $_SESSION['company_id'] = (int) $company['id'];
            }

            $authorization = (new RolePermissionResolver(
                new RbacRepository($connection)
            ))->resolve(
                (int) $user['id_usuario'],
                $company !== null ? (int) $company['id'] : null
            );
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

    public function showPasswordRequired(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['force_password_reset_user_id'])) {
            header('Location: /login');
            exit;
        }

        $this->setView('Login/RecoveryPassword', [
            'title' => 'Alterar senha obrigatória - SwiftlyPark',
            'step' => 'reset',
            'csrfToken' => $this->csrfToken(),
            'forcePasswordReset' => true,
            'success' => 'Antes de continuar, defina uma nova senha.',
        ], false);
    }

    public function updateRequiredPassword(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['force_password_reset_user_id'])
            || !$this->validCsrf()) {
            header('Location: /login');
            exit;
        }

        $password = (string) ($_POST['new_password'] ?? '');
        $confirmation = (string) ($_POST['confirm_password'] ?? '');

        if (strlen($password) < 12 || $password !== $confirmation) {
            $this->setView('Login/RecoveryPassword', [
                'title' => 'Alterar senha obrigatória - SwiftlyPark',
                'step' => 'reset',
                'csrfToken' => $this->csrfToken(),
                'forcePasswordReset' => true,
                'error' => 'Use ao menos 12 caracteres e confirme a mesma senha.',
            ], false);
            return;
        }

        $connection = (new Database())->connect();
        $statement = $connection->prepare(
            'UPDATE usuario
             SET senha_hash = :password_hash, password_reset_required = 0
             WHERE id_usuario = :user_id'
        );
        $statement->execute([
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'user_id' => (int) $_SESSION['force_password_reset_user_id'],
        ]);

        unset($_SESSION['force_password_reset_user_id'], $_SESSION['force_password_reset_email']);
        session_regenerate_id(true);
        header('Location: /login');
        exit;
    }

    private function csrfToken(): string
    {
        if (!isset($_SESSION['login_csrf'])) {
            $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['login_csrf'];
    }

    private function validCsrf(): bool
    {
        return isset($_SESSION['login_csrf'], $_POST['csrf_token'])
            && hash_equals($_SESSION['login_csrf'], (string) $_POST['csrf_token']);
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
