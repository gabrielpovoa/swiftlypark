<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

final class NotificationService
{
    public function accountCreated(
        string $email,
        string $name,
        string $companyName
    ): void {
        if ((string) getenv('MAIL_HOST') === '') {
            return;
        }

        $mailer = new PHPMailer(true);

        try {
            $mailer->isSMTP();
            $mailer->Host = (string) getenv('MAIL_HOST');
            $mailer->SMTPAuth = true;
            $mailer->Username = (string) getenv('MAIL_USERNAME');
            $mailer->Password = (string) getenv('MAIL_PASSWORD');
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->Port = (int) getenv('MAIL_PORT');
            $mailer->CharSet = 'UTF-8';
            $mailer->setFrom(
                (string) getenv('MAIL_USERNAME'),
                (string) (getenv('MAIL_FROM_NAME') ?: 'SwiftlyPark')
            );
            $mailer->addAddress($email, $name);
            $mailer->isHTML(false);
            $mailer->Subject = 'Conta Criada - Bem-vindo ao SwiftlyPark';
            $mailer->Body = sprintf(
                "Olá, %s.\n\nSua conta SwiftlyPark foi criada para a empresa %s.\n"
                . "Use o e-mail %s para entrar. Na primeira autenticação, você deverá alterar sua senha.\n",
                $name,
                $companyName,
                $email
            );
            $mailer->send();
        } catch (Exception $exception) {
            error_log('Falha ao enviar notificação de conta criada: ' . $exception->getMessage());
        }
    }
}
