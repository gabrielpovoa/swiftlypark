<?php
    namespace App\controllers;

    use Core\Controller;

    class ContactController extends Controller
    {
        public function index()
        {
            $this->setView('Contact/contact', [
                'title' => 'Ticket - SwiftlyPark',
            ]);
        }
        public function SendSMTP()
        {
            $nome = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $mensagem = $_POST['message'] ?? '';

            if (!$this->validateField($nome, $email, $mensagem)) {
                $this->setView('Contact/contact', [
                    'title' => 'Ticket - SwiftlyPark',
                    'errorMessage' => 'Por favor, preencha todos os campos corretamente.'
                ]);
                return;
            }

            // Envio de email (lógica real entraria aqui)
            $ticketNumber = strtoupper(uniqid('TK-'));

            $this->setView('Contact/contact', [
                'title' => 'Ticket - SwiftlyPark',
                'successMessage' => 'E-mail enviado com sucesso!',
                'ticketNumber' => $ticketNumber
            ]);
        }


        private function validateField($name,$email,$msg)
        {
            $name = filter_var($name, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);
            $msg = filter_var($msg, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if($name && $email && $msg) {
                return true;
            } else {
                return false;
            }
        }
    }