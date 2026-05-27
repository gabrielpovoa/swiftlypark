<?php
namespace App\Controllers;

use Core\Controller;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
        // Garante que a sessão está ativa para pegar o nome do usuário
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: text/html; charset=UTF-8');

        // 1. Priorizamos o nome da SESSION, se não houver, usamos o do POST ou 'Visitante'
        $nomeUsuarioLogado = $_SESSION['user_name'] ?? ($_POST['name'] ?? 'Visitante');
        $emailCliente      = $_POST['email'] ?? '';
        $mensagem          = $_POST['message'] ?? '';

        if (!$this->validateField($nomeUsuarioLogado, $emailCliente, $mensagem)) {
            $this->setView('Contact/contact', [
                'title' => 'Ticket - SwiftlyPark',
                'errorMessage' => 'Por favor, preencha todos os campos corretamente.'
            ]);
            return;
        }

        $ticketNumber = strtoupper(uniqid('TK-'));
        $subject = "Novo Ticket SwiftlyPark: $ticketNumber";

        // 2. Passamos o $nomeUsuarioLogado para o template do e-mail
        $body = $this->getEmailTemplate(
            "Novo Ticket Recebido",
            $mensagem, // Passamos apenas a mensagem bruta, o template cuida da formatação
            $ticketNumber,
            $nomeUsuarioLogado // Novo parâmetro
        );

        $suporteEmail = getenv('MAIL_USERNAME');

        // 3. Enviamos o e-mail usando o nome do usuário como remetente visual
        if ($this->sendEmail($suporteEmail, $subject, $body, $nomeUsuarioLogado)) {
            $this->setView('Contact/contact', [
                'title' => 'Ticket - SwiftlyPark',
                'successMessage' => 'Sua mensagem foi enviada com sucesso! Em breve entraremos em contato.',
                'ticketNumber' => $ticketNumber
            ]);
        } else {
            $this->setView('Contact/contact', [
                'title' => 'Ticket - SwiftlyPark',
                'errorMessage' => 'Erro ao enviar o email. Tente novamente mais tarde.'
            ]);
        }
    }

    private function validateField($name, $email, $msg)
    {
        $name  = filter_var($name, FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $msg   = strip_tags($msg, '<br><strong><b><i><u><p>');

        return $name && $email && $msg;
    }

    // Atualizado para aceitar o clientName e passar para a View do e-mail
    private function getEmailTemplate($title, $message, $ticketNumber, $clientName)
    {
        ob_start();
        // As variáveis abaixo ficam disponíveis dentro de ticket.php
        $data = [
            'title' => $title,
            'message' => $message,
            'ticketNumber' => $ticketNumber,
            'clientName' => $clientName
        ];
        extract($data);

        include __DIR__ . '/../Views/Contact/ticket.php';
        return ob_get_clean();
    }

    private function sendEmail($to, $subject, $body, $fromName = null)
    {
        $mail = new PHPMailer(true);
        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isHTML(true);

        try {
            $mail->isSMTP();
            $mail->Host       = getenv('MAIL_HOST');
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('MAIL_USERNAME');
            $mail->Password   = getenv('MAIL_PASSWORD');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = getenv('MAIL_PORT');

            // Configuramos o remetente fixo (seu e-mail SMTP) mas com o nome do usuário
            $mail->setFrom(getenv('MAIL_USERNAME'), $fromName ?? getenv('MAIL_FROM_NAME'));
            $mail->addAddress($to, 'Equipe SwiftlyPark');

            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Em produção, logue o erro em vez de dar echo
            return false;
        }
    }
}