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
            $name  = filter_var($name, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);
            $msg   = filter_var($msg, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            return $name && $email && $msg;
        }

        private function getEmailTemplate($title, $message, $ticketNumber = null)
        {
            $ticketHtml = $ticketNumber ? "<p>Seu ticket: <strong>{$ticketNumber}</strong></p>" : '';
            ob_start();
            include __DIR__ . '/../Views/Contact/ticket.php';
            return ob_get_clean();
        }

        private function sendEmail($to, $subject, $body, $fromName = 'SwiftlyPark')
        {
            $mail = new PHPMailer(true);

            try {
                // Configurações básicas SMTP
                $mail->isSMTP();
                $mail->SMTPDebug = 0; // 0 = silencioso, 2 = debug detalhado
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'condedeleau@gmail.com'; // seu Gmail
                $mail->Password = 'jrhltcbvvbpjbomp';     // senha de app correta, sem espaços
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // STARTTLS
                $mail->Port = 587;

                // Mantém conexão aberta (útil para múltiplos envios)
                $mail->SMTPKeepAlive = true;

                // Remetente e destinatário
                $mail->setFrom('condedeleau@gmail.com', 'SwiftlyPark Test');
                $mail->addAddress($to, $fromName);

                // Conteúdo do email
                $mail->Subject = $subject;
                $mail->Body = $body;
                $mail->isHTML(true);

                // Envia
                $mail->send();

                // Fecha a conexão SMTP
                $mail->smtpClose();

                return true;
            } catch (Exception $e) {
                echo "<pre>Erro PHPMailer: {$mail->ErrorInfo}</pre>";
                return false;
            }
        }
    }
