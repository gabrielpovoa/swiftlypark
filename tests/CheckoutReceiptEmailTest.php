<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Services\CheckoutReceiptMailer;

$root = dirname(__DIR__);
$gateway = file_get_contents($root . '/app/Parking/Infrastructure/PdoParkingGateway.php');
$worker = file_get_contents($root . '/app/Shared/Application/JobWorker.php');
$queue = file_get_contents($root . '/app/Services/QueuedCheckoutReceiptMailer.php');

foreach ([$gateway, $worker, $queue] as $source) {
    if ($source === false) {
        throw new RuntimeException('Não foi possível carregar o fluxo do recibo.');
    }
}

foreach ([
    "'recipient_email' => IdentityContext::current()->email()",
    "'mail.checkout_receipt'",
    'QueuedCheckoutReceiptMailer::fromConnection($this->db)',
    "'receipt_queued' => true",
] as $marker) {
    if (!str_contains($gateway . $worker . $queue, $marker)) {
        throw new RuntimeException('Integração do recibo incompleta: ' . $marker);
    }
}

$mailer = new CheckoutReceiptMailer();
$method = new ReflectionMethod($mailer, 'html');
$html = $method->invoke($mailer, [
    'company_name' => '<script>empresa()</script>SafePark',
    'customer_name' => '<img src=x onerror=alert(1)>Cliente',
    'plate' => 'ABC-1D23',
    'entry_at' => '19/07/2026 10:00',
    'exit_at' => '19/07/2026 11:00',
    'duration_minutes' => 60,
    'billing_model' => 'ROTATING',
    'payment_method' => 'PIX',
    'amount' => 12.50,
    'stay_id' => 27,
]);

if (str_contains($html, '<script>') || str_contains($html, '<img src=x')) {
    throw new RuntimeException('O template do recibo aceitou HTML do payload.');
}
if (!str_contains($html, 'R$ 12,50') || !str_contains($html, 'ABC-1D23')) {
    throw new RuntimeException('O recibo não apresenta os dados essenciais.');
}

echo "Checkout receipt email test passed\n";
