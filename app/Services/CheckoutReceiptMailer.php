<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CheckoutReceiptMailerInterface;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

final class CheckoutReceiptMailer implements CheckoutReceiptMailerInterface
{
    public function sendReceipt(array $receipt): void
    {
        $email = filter_var((string) ($receipt['recipient_email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if ($email === false) {
            throw new RuntimeException('Destinatário inválido para o recibo de checkout.');
        }

        $mailer = new PHPMailer(true);

        try {
            $this->configureSmtp($mailer);
            $mailer->addAddress($email);
            $mailer->isHTML(true);
            $mailer->Subject = sprintf(
                'Recibo de estacionamento - %s',
                $this->plain($receipt['company_name'] ?? 'SwiftlyPark')
            );
            $mailer->Body = $this->html($receipt);
            $mailer->AltBody = $this->text($receipt);
            $mailer->send();
        } catch (Exception $exception) {
            throw new RuntimeException('Não foi possível enviar o recibo por e-mail.', 0, $exception);
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
        $mailer->setFrom($username, (string) (getenv('MAIL_FROM_NAME') ?: 'SwiftlyPark'));
    }

    /** @param array<string, mixed> $receipt */
    private function html(array $receipt): string
    {
        $company = $this->escape($receipt['company_name'] ?? 'SwiftlyPark');
        $plate = $this->escape($receipt['plate'] ?? 'Não informada');
        $customer = $this->escape($receipt['customer_name'] ?? 'Não informado');
        $entry = $this->escape($receipt['entry_at'] ?? '');
        $exit = $this->escape($receipt['exit_at'] ?? '');
        $duration = (int) ($receipt['duration_minutes'] ?? 0);
        $monthly = ($receipt['billing_model'] ?? '') === 'MONTHLY';
        $model = $monthly ? 'Mensalista' : 'Rotativo';
        $payment = $monthly ? 'Coberto pelo contrato mensal' : $this->escape($receipt['payment_method'] ?? '');
        $amount = number_format((float) ($receipt['amount'] ?? 0), 2, ',', '.');
        $reference = (int) ($receipt['stay_id'] ?? 0);

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Recibo de estacionamento</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,sans-serif;color:#0f172a">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:32px 16px"><tr><td align="center">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#fff;border-radius:20px;overflow:hidden">
<tr><td style="padding:28px 32px;background:#0f172a;color:#fff"><div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#93c5fd">{$company}</div><h1 style="margin:8px 0 0;font-size:26px">Recibo de estacionamento</h1></td></tr>
<tr><td style="padding:30px 32px"><p style="margin-top:0;color:#475569">A saída foi registrada com sucesso pelo sistema.</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="8" style="border-collapse:collapse">
<tr><td style="color:#64748b">Referência</td><td align="right"><strong>#{$reference}</strong></td></tr>
<tr><td style="color:#64748b">Cliente</td><td align="right"><strong>{$customer}</strong></td></tr>
<tr><td style="color:#64748b">Placa</td><td align="right"><strong>{$plate}</strong></td></tr>
<tr><td style="color:#64748b">Entrada</td><td align="right">{$entry}</td></tr>
<tr><td style="color:#64748b">Saída</td><td align="right">{$exit}</td></tr>
<tr><td style="color:#64748b">Permanência</td><td align="right">{$duration} minutos</td></tr>
<tr><td style="color:#64748b">Cobrança</td><td align="right">{$model}</td></tr>
<tr><td style="color:#64748b">Pagamento</td><td align="right">{$payment}</td></tr>
<tr style="border-top:1px solid #e2e8f0"><td style="padding-top:18px;font-size:17px">Valor</td><td align="right" style="padding-top:18px;font-size:22px"><strong>R$ {$amount}</strong></td></tr>
</table><p style="margin:26px 0 0;color:#94a3b8;font-size:12px">Documento de controle operacional, sem valor fiscal.</p></td></tr>
</table></td></tr></table></body></html>
HTML;
    }

    /** @param array<string, mixed> $receipt */
    private function text(array $receipt): string
    {
        $monthly = ($receipt['billing_model'] ?? '') === 'MONTHLY';
        $payment = $monthly
            ? 'Coberto pelo contrato mensal'
            : $this->plain($receipt['payment_method'] ?? '');

        return sprintf(
            "Recibo de estacionamento - %s\nReferência: #%d\nCliente: %s\nPlaca: %s\nEntrada: %s\nSaída: %s\nPermanência: %d minutos\nCobrança: %s\nPagamento: %s\nValor: R$ %s\n\nDocumento sem valor fiscal.",
            $this->plain($receipt['company_name'] ?? 'SwiftlyPark'),
            (int) ($receipt['stay_id'] ?? 0),
            $this->plain($receipt['customer_name'] ?? 'Não informado'),
            $this->plain($receipt['plate'] ?? 'Não informada'),
            $this->plain($receipt['entry_at'] ?? ''),
            $this->plain($receipt['exit_at'] ?? ''),
            (int) ($receipt['duration_minutes'] ?? 0),
            $monthly ? 'Mensalista' : 'Rotativo',
            $payment,
            number_format((float) ($receipt['amount'] ?? 0), 2, ',', '.')
        );
    }

    private function escape(mixed $value): string
    {
        return htmlspecialchars($this->plain($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function plain(mixed $value): string
    {
        return trim(strip_tags((string) $value));
    }
}
