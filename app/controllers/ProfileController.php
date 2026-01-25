<?php

namespace App\Controllers;

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

        $this->setView('Profile/profile', [
            'title' => 'Perfil - SwiftlyPark',
            'user' => $user
        ]);
    }


    public function changePassword()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $senhaAtual = $_POST['current_password'] ?? null;
        $novaSenha = $_POST['new_password'] ?? null;

        if (!$senhaAtual || !$novaSenha) {

            $userModel = new User();
            $user = $userModel->getUserById($_SESSION['user_id']);

            $this->setView('Profile/profile', [
                'title' => 'Perfil - SwiftlyPark',
                'message' => 'Preencha todos os campos.',
                'user' => $user
            ]);
            return;
        }

        $userModel = new User();
        $resultado = $userModel->changePassword($_SESSION['user_id'], $senhaAtual, $novaSenha);

        // 🔥 Buscar novamente o usuário aqui
        $user = $userModel->getUserById($_SESSION['user_id']);

        $this->setView('Profile/profile', [
            'title' => 'Perfil - SwiftlyPark',
            'message' => $resultado['message'],
            'user' => $user
        ]);
        return;
    }

    // GET - também precisa enviar o usuário
    $userModel = new User();
    $user = $userModel->getUserById($_SESSION['user_id']);

    $this->setView('Profile/profile', [
        'title' => 'Perfil - SwiftlyPark',
        'user' => $user
    ]);
}



    public function uploadPhoto()
    {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }

        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== 0) {
            die("Erro no upload da foto.");
        }

        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Extensões válidas
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            die("Formato inválido. Envie JPG, PNG ou WEBP.");
        }

        // Nome único
        $fileName = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;

        // Caminho correto: /public/uploads/
        $uploadDir = __DIR__ . '/../../public/uploads/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $destPath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            die("Erro ao mover arquivo de upload.");
        }

        // Atualiza banco
        $model = new User();
        $update = $model->updatePhoto($_SESSION['user_id'], $fileName);

        if ($update['success']) {
            // Atualiza sessão
            $_SESSION['user_photo'] = $fileName;

            header("Location: /Profile");
            exit;
        }

        die("Erro ao atualizar a foto no banco.");
    }
}
