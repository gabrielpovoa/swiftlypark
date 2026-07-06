<?php

namespace App\Controllers;

use App\Authorization\Repositories\RbacRepository;
use App\Context\IdentityContext;
use App\Services\AuthorizationService;
use Config\Database;
use Core\Controller;
use App\Models\User;

class ProfileController extends Controller
{
    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }

        $userModel = new User();
        $user = $userModel->getUserById($_SESSION['user_id']);

        $this->renderProfile($user);
    }


    public function changePassword()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $senhaAtual = $_POST['current_password'] ?? null;
        $novaSenha = $_POST['new_password'] ?? null;

        if (!$senhaAtual || !$novaSenha) {

            $userModel = new User();
            $user = $userModel->getUserById($_SESSION['user_id']);

            $this->renderProfile($user, 'Preencha todos os campos.');
            return;
        }

        $userModel = new User();
        $resultado = $userModel->changePassword($_SESSION['user_id'], $senhaAtual, $novaSenha);

        // 🔥 Buscar novamente o usuário aqui
        $user = $userModel->getUserById($_SESSION['user_id']);

        $this->renderProfile($user, $resultado['message']);
        return;
    }

    // GET - também precisa enviar o usuário
    $userModel = new User();
    $user = $userModel->getUserById($_SESSION['user_id']);

    $this->renderProfile($user);
}



    public function uploadPhoto(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }

        $file = $_FILES['photo'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            error_log('Falha ao receber foto; código de upload: ' . ($file['error'] ?? 'ausente'));
            http_response_code(400);
            exit("Não foi possível receber a foto.");
        }

        $maxFileSize = 5 * 1024 * 1024;

        if ($file['size'] <= 0 || $file['size'] > $maxFileSize || !is_uploaded_file($file['tmp_name'])) {
            http_response_code(400);
            exit("Arquivo inválido ou maior que 5 MB.");
        }

        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

        if (!isset($allowedMimeTypes[$mimeType])) {
            http_response_code(415);
            exit("Formato inválido. Envie JPG, PNG ou WEBP.");
        }

        $fileName = sprintf(
            'user_%d_%s.%s',
            (int) $_SESSION['user_id'],
            bin2hex(random_bytes(16)),
            $allowedMimeTypes[$mimeType]
        );

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads';

        if ((!is_dir($uploadDir) && !mkdir($uploadDir, 0750, true) && !is_dir($uploadDir))
            || !is_writable($uploadDir)
        ) {
            error_log("Diretório de upload indisponível ou sem escrita: {$uploadDir}");
            http_response_code(500);
            exit("Não foi possível salvar a foto.");
        }

        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        // A validação de escrita acima fornece o diagnóstico esperado. O operador
        // evita que uma condição de corrida exponha caminhos internos ao usuário.
        if (!@move_uploaded_file($file['tmp_name'], $destPath)) {
            $lastError = error_get_last();
            error_log('Falha ao mover foto: ' . ($lastError['message'] ?? 'erro desconhecido'));
            http_response_code(500);
            exit("Não foi possível salvar a foto.");
        }

        $model = new User();
        $update = $model->updatePhoto($_SESSION['user_id'], $fileName);

        if ($update['success']) {
            $_SESSION['user_photo'] = $fileName;

            header("Location: /Profile");
            exit;
        }

        if (is_file($destPath) && !unlink($destPath)) {
            error_log("Não foi possível remover o upload órfão: {$destPath}");
        }

        error_log('Falha ao atualizar foto no banco: ' . ($update['error'] ?? 'erro desconhecido'));
        http_response_code(500);
        exit("Não foi possível atualizar a foto.");
    }

    private function renderProfile(array $user, ?string $message = null): void
    {
        $identity = IdentityContext::current();
        $authorization = new AuthorizationService($identity);
        $connection = (new Database())->connect();

        $this->setView('Profile/profile', [
            'title' => 'Perfil - SwiftlyPark',
            'user' => $user,
            'message' => $message,
            'roleMetadata' => $identity->roleMetadata(),
            'roleSlugs' => $identity->roleSlugs(),
            'permissionLabels' => (new RbacRepository($connection))
                ->findPermissionLabels($identity->permissions()),
            'canChangePassword' => $authorization
                ->can('profile.password.update'),
            'canChangePhoto' => $authorization
                ->can('profile.photo.update'),
        ]);
    }
}
