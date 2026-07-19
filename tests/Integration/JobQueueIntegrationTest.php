<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Shared\Infrastructure\Queue\PdoJobQueue;
use App\Shared\Infrastructure\Security\EncryptedPayload;
use Config\Database;

$a = (new Database())->connect(); $b = (new Database())->connect();
$cipher = EncryptedPayload::fromEnvironment();
$queueA = new PdoJobQueue($a); $queueB = new PdoJobQueue($b);
$marker = bin2hex(random_bytes(8));
$idA = $queueA->enqueue('test.queue', $cipher->encrypt(['marker' => $marker . '-a']), 'integration', 1);
$idB = $queueA->enqueue('test.queue', $cipher->encrypt(['marker' => $marker . '-b']), 'integration', 1);
try {
    $a->beginTransaction();
    $lock = $a->prepare('SELECT id FROM background_jobs WHERE id = :id FOR UPDATE');
    $lock->execute(['id' => $idA]);
    $reserved = $queueB->reserve('integration');
    if ((int) ($reserved['id'] ?? 0) !== $idB) {
        throw new RuntimeException('SKIP LOCKED não entregou o segundo job ao worker concorrente.');
    }
    $queueB->release($idB, '<script>erro temporário</script>', 1);
    $status = $b->query('SELECT status, last_error FROM background_jobs WHERE id = ' . $idB)->fetch(PDO::FETCH_ASSOC);
    if ($status['status'] !== 'FAILED' || str_contains((string) $status['last_error'], '<script>')) {
        throw new RuntimeException('Retry final/sanitização do job divergiu.');
    }
    echo "Background job MySQL concurrency/retry test passed\n";
} finally {
    if ($a->inTransaction()) $a->rollBack();
    $delete = $b->prepare('DELETE FROM background_jobs WHERE id IN (:id_a, :id_b)');
    $delete->execute(['id_a' => $idA, 'id_b' => $idB]);
}

$completedId = $queueB->enqueue('test.complete', $cipher->encrypt(['secret' => 'must-disappear']), 'integration');
try {
    $completed = $queueB->reserve('integration');
    $queueB->complete((int) $completed['id']);
    $row = $b->query('SELECT status, encrypted_payload FROM background_jobs WHERE id = ' . $completedId)
        ->fetch(PDO::FETCH_ASSOC);
    if ($row['status'] !== 'COMPLETED' || $row['encrypted_payload'] !== '') {
        throw new RuntimeException('Conclusão não eliminou o payload sensível.');
    }
    echo "Completed job payload cleanup passed\n";
} finally {
    $b->exec('DELETE FROM background_jobs WHERE id = ' . $completedId);
}
