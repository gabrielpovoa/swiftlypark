<?php
declare(strict_types=1);
namespace App\Shared\Infrastructure\Security;
use RuntimeException;
final class EncryptedPayload
{
    private readonly string $key;
    public function __construct(string $secret)
    {
        if (strlen($secret) < 24) { throw new RuntimeException('JOB_QUEUE_KEY deve possuir ao menos 24 caracteres.'); }
        $this->key = hash('sha256', $secret, true);
    }
    public static function fromEnvironment(): self
    {
        return new self((string) getenv('JOB_QUEUE_KEY'));
    }
    public function encrypt(array $payload): string
    {
        $nonce = random_bytes(12); $tag = '';
        $plain = json_encode($payload, JSON_THROW_ON_ERROR);
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $this->key,
            OPENSSL_RAW_DATA, $nonce, $tag, 'swiftlypark-job-v1', 16);
        if ($cipher === false) { throw new RuntimeException('Falha ao criptografar payload do job.'); }
        return base64_encode($nonce . $tag . $cipher);
    }
    public function decrypt(string $encoded): array
    {
        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 29) { throw new RuntimeException('Payload criptografado inválido.'); }
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $this->key,
            OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16), 'swiftlypark-job-v1');
        if ($plain === false) { throw new RuntimeException('Autenticação do payload do job falhou.'); }
        $payload = json_decode($plain, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) { throw new RuntimeException('Payload do job não é um objeto.'); }
        return $payload;
    }
}
