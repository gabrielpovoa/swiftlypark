<?php

declare(strict_types=1);

namespace App\Services;

use App\Context\TenantContext;
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
            $this->configureSmtp($mailer);
            $mailer->addAddress($email);
            $mailer->isHTML(true);
            $mailer->Subject = 'Código de recuperação SwiftlyPark';
            $mailer->Body = $this->buildHtmlTemplate(
                'Recuperação de acesso',
                'Use o código abaixo para concluir a recuperação da sua senha.',
                sprintf(
                    'Seu código de recuperação é <strong>%s</strong>. Ele expira em <strong>%d minutos</strong>.',
                    $otp,
                    $ttlMinutes
                ),
                'Se você não solicitou esta recuperação, pode ignorar este e-mail com segurança.'
            );
            $mailer->AltBody = sprintf(
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

    public function sendTemporaryPassword(string $email, string $temporaryPassword): void
    {
        $this->sendPasswordMessage(
            $email,
            $temporaryPassword,
            'Senha temporária SwiftlyPark',
            'Senha temporária criada',
            'Uma senha temporária foi criada para você. Use-a para acessar o sistema e troque imediatamente ao entrar.'
        );
    }

    public function sendReactivationPassword(string $email, string $temporaryPassword): void
    {
        $this->sendPasswordMessage(
            $email,
            $temporaryPassword,
            'Acesso reativado - SwiftlyPark',
            'Acesso reativado',
            'Seu acesso foi reativado. Use a senha temporária abaixo para entrar e definir uma nova senha.'
        );
    }

    private function sendPasswordMessage(
        string $email,
        string $temporaryPassword,
        string $subject,
        string $title,
        string $subtitle
    ): void
    {
        $mailer = new PHPMailer(true);

        try {
            $this->configureSmtp($mailer);
            $mailer->addAddress($email);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $this->buildHtmlTemplate(
                $title,
                $subtitle,
                sprintf(
                    'Sua senha temporária é <strong>%s</strong>.',
                    $temporaryPassword
                ),
                'Ao entrar no sistema, você será solicitado a definir uma nova senha.'
            );
            $mailer->AltBody = $subtitle . "\n"
                . "Senha temporária: {$temporaryPassword}\n"
                . "Ao entrar no sistema, você será solicitado a alterar essa senha imediatamente.";
            $mailer->send();
        } catch (Exception $exception) {
            throw new RuntimeException(
                'Não foi possível enviar a senha temporária por e-mail.',
                0,
                $exception
            );
        }
    }

    private function configureSmtp(PHPMailer $mailer): void
    {
        $host = trim((string) getenv('MAIL_HOST'));
        $username = trim((string) getenv('MAIL_USERNAME'));
        $password = (string) getenv('MAIL_PASSWORD');
        $port = (int) getenv('MAIL_PORT');

        if ($host === '' || $username === '' || $password === '' || $port < 1) {
            throw new RuntimeException('Configuração SMTP incompleta no ambiente do worker.');
        }

        $encryption = strtolower(trim((string) (getenv('MAIL_ENCRYPTION') ?: 'tls')));

        $mailer->isSMTP();
        $mailer->Host = $host;
        $mailer->SMTPAuth = true;
        $mailer->Username = $username;
        $mailer->Password = $password;
        $mailer->Port = $port;
        $mailer->CharSet = 'UTF-8';
        $mailer->SMTPSecure = match ($encryption) {
            'ssl', 'smtps' => PHPMailer::ENCRYPTION_SMTPS,
            'none', '' => '',
            default => PHPMailer::ENCRYPTION_STARTTLS,
        };
        $mailer->setFrom(
            $username,
            (string) (getenv('MAIL_FROM_NAME') ?: 'SwiftlyPark')
        );
    }

    private function buildHtmlTemplate(string $title, string $subtitle, string $highlight, string $footer): string
    {
        $company = TenantContext::instance()->getCompany();
        $companyName = $company?->name() ?? 'SwiftlyPark';
        $logoUrl = $this->logoUrl($company?->logoPath());

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f7fb; font-family:Arial, Helvetica, sans-serif; color:#14213d;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f7fb; padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" max-width="620" cellspacing="0" cellpadding="0" border="0" style="max-width:620px; background:#ffffff; border-radius:20px; overflow:hidden; box-shadow:0 16px 40px rgba(20, 33, 61, 0.08);">
          <tr>
            <td style="background:linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%); padding:28px 32px;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                  <td align="left">
                    <div style="display:inline-block;">
                      {$this->logoMarkup($logoUrl, $companyName)}
                    </div>
                  </td>
                </tr>
                <tr>
                  <td style="padding-top:16px;">
                    <div style="font-size:11px; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:#bfdbfe;">SwiftlyPark</div>
                    <div style="font-size:26px; font-weight:700; color:#ffffff; margin-top:4px;">{$title}</div>
                    <div style="font-size:15px; color:#dbeafe; margin-top:8px;">{$subtitle}</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:32px;">
              <div style="background:#f8fbff; border:1px solid #dbeafe; border-radius:16px; padding:24px; text-align:center;">
                <div style="font-size:14px; color:#64748b; margin-bottom:12px; text-transform:uppercase; letter-spacing:1.5px; font-weight:700;">Informação importante</div>
                <div style="font-size:22px; line-height:1.4; color:#0f172a; font-weight:700;">{$highlight}</div>
              </div>
              <div style="margin-top:24px; font-size:15px; line-height:1.7; color:#475569;">{$footer}</div>
              <div style="margin-top:24px; padding-top:20px; border-top:1px solid #e2e8f0; font-size:13px; color:#64748b;">
                Este e-mail foi enviado automaticamente pela plataforma SwiftlyPark para {$companyName}.
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    private function logoMarkup(?string $logoUrl, string $companyName): string
    {
        if ($logoUrl === null || $logoUrl === '') {
            return sprintf(
                '<div style="font-size:18px; font-weight:700; color:#ffffff;">%s</div>',
                htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8')
            );
        }

        return sprintf(
            '<img src="%s" alt="%s" style="max-height:48px; max-width:180px; display:block; border-radius:10px; background:#ffffff; padding:6px;">',
            htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8')
        );
    }

    private function logoUrl(?string $logoPath): ?string
    {
        if ($logoPath === null || $logoPath === '') {
            return null;
        }

        $baseUrl = getenv('APP_URL') ?: ($_SERVER['HTTP_HOST'] ?? 'http://localhost');
        if (!preg_match('#^https?://#i', $baseUrl)) {
            $baseUrl = 'http://' . $baseUrl;
        }

        $normalizedPath = ltrim((string) $logoPath, '/');

        return rtrim($baseUrl, '/') . '/uploads/' . $normalizedPath;
    }
}
