<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PasswordRecoveryMailerInterface;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

final class PasswordRecoveryMailer implements PasswordRecoveryMailerInterface
{
    public function sendOtp(string $email, string $otp, int $ttlMinutes): void
    {
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
            $mailer->addAddress($email);
            $mailer->isHTML(false);
            $mailer->Subject = 'Código de recuperação SwiftlyPark';
            $mailer->Body = sprintf(
                "Seu código de recuperação é %s. Ele expira em %d minutos.\n"
                . 'Se você não solicitou a recuperação, ignore esta mensagem.',
                $otp,
                $ttlMinutes
            );
            $mailer->send();
        } catch (Exception $exception) {
            throw new RuntimeException(
                'Não foi possível enviar o código de recuperação.',
                0,
                $exception
            );
        }
    }
}
