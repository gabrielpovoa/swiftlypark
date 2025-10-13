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
            header('Content-Type: text/html; charset=UTF-8');

            $nome     = $_POST['name'] ?? '';
            $email    = $_POST['email'] ?? '';
            $mensagem = $_POST['message'] ?? '';

            if (!$this->validateField($nome, $email, $mensagem)) {
                $this->setView('Contact/contact', [
                    'title' => 'Ticket - SwiftlyPark',
                    'errorMessage' => 'Por favor, preencha todos os campos corretamente.'
                ]);
                return;
            }

            $ticketNumber = strtoupper(uniqid('TK-'));
            $subject = "Novo Ticket SwiftlyPark: $ticketNumber";
            $body = $this->getEmailTemplate(
                "Novo Ticket Recebido",
                "Olá {$nome},<br>Recebemos sua mensagem: <br><strong>{$mensagem}</strong>",
                $ticketNumber
            );

            if ($this->sendEmail($email, $subject, $body)) {
                $this->setView('Contact/contact', [
                    'title' => 'Ticket - SwiftlyPark',
                    'successMessage' => 'E-mail enviado com sucesso!',
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
            $name  = filter_var($name, FILTER_SANITIZE_SPECIAL_CHARS); // mantém acentos e caracteres normais
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);

            // Apenas remove tags maliciosas do texto, mas preserva <br>, <strong> etc
            $msg = strip_tags($msg, '<br><strong><b><i><u><p>');

            return $name && $email && $msg;
        }

        private function getEmailTemplate($title, $message, $ticketNumber = null)
        {
            $ticketHtml = $ticketNumber ? "<p>Seu ticket: <strong>{$ticketNumber}</strong></p>" : '';
            ob_start();
            include __DIR__ . '/../Views/Contact/ticket.php';
            return ob_get_clean();
        }

        private function sendEmail($to, $subject, $body, $fromName = null)
        {
            $mail = new PHPMailer(true);

            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isHTML(true);
            $mail->ContentType = 'text/html; charset=UTF-8';

            try {
                $mail->isSMTP();
                $mail->Host       = getenv('MAIL_HOST');
                $mail->SMTPAuth   = true;
                $mail->Username   = getenv('MAIL_USERNAME');
                $mail->Password   = getenv('MAIL_PASSWORD');
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = getenv('MAIL_PORT');
                $mail->SMTPKeepAlive = true;

                $fromName = $fromName ?? getenv('MAIL_FROM_NAME');
                $mail->setFrom(getenv('MAIL_USERNAME'), $fromName);
                $mail->addAddress($to, $fromName);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;

                $mail->send();
                $mail->smtpClose();

                return true;
            } catch (Exception $e) {
                echo "<pre>Erro PHPMailer: {$mail->ErrorInfo}</pre>";
                return false;
            }
        }

    }
