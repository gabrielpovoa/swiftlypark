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

            // Gera ticket único
            $ticketNumber = strtoupper(uniqid('TK-'));
            $subject = "Novo Ticket SwiftlyPark: $ticketNumber";
            $body = $this->getEmailTemplate(
                "Novo Ticket Recebido",
                "Olá {$nome},<br>Recebemos sua mensagem: <br><strong>{$mensagem}</strong>",
                $ticketNumber
            );

            // Envia o email
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

        // Valida e sanitiza campos
        private function validateField($name, $email, $msg)
        {
            $name = filter_var($name, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);
            $msg = filter_var($msg, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            return $name && $email && $msg;
        }

        // Template HTML padrão para emails
        private function getEmailTemplate($title, $message, $ticketNumber = null)
        {
            $ticketHtml = $ticketNumber ? "<p>Seu ticket: <strong>{$ticketNumber}</strong></p>" : '';
            return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .header { background: #364574; color: #fff; padding: 10px; text-align: center; }
                .content { padding: 20px; background: #f5f5f5; }
                .footer { padding: 10px; font-size: 12px; color: #888; text-align: center; }
            </style>
        </head>
        <body>
            <div class='header'><h2>{$title}</h2></div>
            <div class='content'>
                <p>{$message}</p>
                {$ticketHtml}
            </div>
            <div class='footer'>
                SwiftlyPark &copy; ".date('Y')."
            </div>
        </body>
        </html>
        ";
        }

        // Função para envio de email via SMTP
        private function sendEmail($to, $subject, $body, $fromName = 'SwiftlyPark', $fromEmail = 'no-reply@swiftlypark.com')
        {
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; // Ex.: smtp.gmail.com
                $mail->SMTPAuth   = true;
                $mail->Username   = 'condeleau@gmail.com'; // Usuário SMTP
                $mail->Password   = 'dmvk jreg pvqz egey ';           // Senha ou token
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 465;                   // 465 para SMTPS

                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($to);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;

                $mail->send();
                return true;
            } catch (Exception $e) {
                error_log("Erro ao enviar email: " . $mail->ErrorInfo);
                return false;
            }
        }
    }
