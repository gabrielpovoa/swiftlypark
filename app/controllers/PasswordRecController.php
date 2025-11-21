<?php
namespace App\Controllers;

use Core\Controller;
use App\models\PasswordRecovery;

class PasswordRecController extends Controller
{
    // Exibe a tela de recuperação
    public function showForm()
    {
        $this->setView('login/RecoveryPassword', [
            'title' => 'Recupere Sua Conta',
            'message' => 'Por favor, insira seu e-mail'
        ], false);
    }

    // Recebe o formulário e envia o e-mail
    public function sendRecoveryLink()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

            if (!$email) {
                $this->setView('login/RecoveryPassword', [
                    'title' => 'Recupere Sua Conta',
                    'error' => 'Digite um e-mail válido!'
                ]);
                return;
            }

            $passwordRecovery = new PasswordRecovery();
            $user = $passwordRecovery->getUserByEmail($email);

            if ($user) {
                // Se o campo 'new_password' estiver preenchido, atualizamos a senha
                if (!empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
                    $newPassword = $_POST['new_password'];
                    $confirmPassword = $_POST['confirm_password'];

                    if ($newPassword !== $confirmPassword) {
                        $this->setView('login/RecoveryPassword', [
                            'title' => 'Recupere Sua Conta',
                            'email' => $email,
                            'showNewPassword' => true,
                            'error' => 'As senhas não coincidem!'
                        ], false);
                        return;
                    }

                    $passwordRecovery->updatePassword($user['id_usuario'], $newPassword);

                    $this->setView('login/RecoveryPassword', [
                        'title' => 'Recupere Sua Conta',
                        'success' => 'Senha atualizada com sucesso!'
                    ], false);
                    return;
                }

                // Exibe o formulário para inserir nova senha
                $this->setView('login/RecoveryPassword', [
                    'title' => 'Recupere Sua Conta',
                    'email' => $email,
                    'showNewPassword' => true
                ], false);
            } else {
                $this->setView('login/RecoveryPassword', [
                    'title' => 'Recupere Sua Conta',
                    'error' => 'E-mail não encontrado em nosso sistema.'
                ], false);
            }
        }
    }

}
