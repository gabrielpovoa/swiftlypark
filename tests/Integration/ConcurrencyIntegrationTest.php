<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Config\Database;

function connection(): PDO
{
    $pdo = (new Database())->connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function expectLockConflict(callable $operation, string $scenario): void
{
    try {
        $operation();
        throw new RuntimeException('Concorrência não foi bloqueada em ' . $scenario);
    } catch (PDOException $exception) {
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        if (!in_array($driverCode, [1062, 1205, 1213], true)) {
            throw $exception;
        }
    }
}

$a = connection();
$b = connection();
$b->exec('SET SESSION innodb_lock_wait_timeout = 1');

// Check-in: a constraint deve serializar/rejeitar duas estadias abertas.
$vacancy = $a->query(
    'SELECT vd.company_id, vd.id_vaga, vd.categoria
     FROM vagas_disponiveis vd
     LEFT JOIN vagas_preenchidas vp ON vp.company_id = vd.company_id
       AND vp.id_vaga = vd.id_vaga AND vp.hora_saida IS NULL
     WHERE vp.id_vaga_preenchida IS NULL
     ORDER BY vd.id_vaga LIMIT 1'
)->fetch(PDO::FETCH_ASSOC);
$userId = $a->query('SELECT id_usuario FROM usuario WHERE deleted_at IS NULL ORDER BY id_usuario LIMIT 1')
    ->fetchColumn();
if ($vacancy === false || $userId === false) {
    throw new RuntimeException('Fixture para concorrência de check-in indisponível.');
}
$insert = 'INSERT INTO vagas_preenchidas (company_id, id_vaga, hora_entrada, nome_cliente,
 telefone, placa, valor_pago, tipo_veiculo, billing_model, created_by, updated_by)
 VALUES (:company_id, :id_vaga, UTC_TIMESTAMP(), "Concorrência", "41999999999",
 :plate, 0, :type, "ROTATING", :created_by, :updated_by)';
$a->beginTransaction();
$b->beginTransaction();
try {
    $statement = $a->prepare($insert);
    $statement->execute(['company_id' => $vacancy['company_id'], 'id_vaga' => $vacancy['id_vaga'],
        'plate' => 'TST' . random_int(1000, 9999), 'type' => $vacancy['categoria'],
        'created_by' => $userId, 'updated_by' => $userId]);
    $filledA = (int) $a->lastInsertId();
    $active = $a->prepare(
        'INSERT INTO parking_active_stays (company_id, vacancy_id, filled_vacancy_id)
         VALUES (:company_id, :vacancy_id, :filled_id)'
    );
    $active->execute(['company_id' => $vacancy['company_id'], 'vacancy_id' => $vacancy['id_vaga'],
        'filled_id' => $filledA]);
    expectLockConflict(function () use ($b, $insert, $vacancy, $userId): void {
        $statement = $b->prepare($insert);
        $statement->execute(['company_id' => $vacancy['company_id'], 'id_vaga' => $vacancy['id_vaga'],
            'plate' => 'TSB' . random_int(1000, 9999), 'type' => $vacancy['categoria'],
            'created_by' => $userId, 'updated_by' => $userId]);
        $active = $b->prepare(
            'INSERT INTO parking_active_stays (company_id, vacancy_id, filled_vacancy_id)
             VALUES (:company_id, :vacancy_id, :filled_id)'
        );
        $active->execute(['company_id' => $vacancy['company_id'], 'vacancy_id' => $vacancy['id_vaga'],
            'filled_id' => (int) $b->lastInsertId()]);
    }, 'check-in');
    echo "Concurrent check-in protection passed\n";
} finally {
    if ($b->inTransaction()) { $b->rollBack(); }
    if ($a->inTransaction()) { $a->rollBack(); }
}

// Checkout: a estadia ativa deve ser bloqueada pessimisticamente.
$activeStay = $a->query(
    'SELECT company_id, id_vaga_preenchida FROM vagas_preenchidas
     ORDER BY id_vaga_preenchida LIMIT 1'
)->fetch(PDO::FETCH_ASSOC);
if ($activeStay === false) {
    throw new RuntimeException('Fixture para concorrência de checkout indisponível.');
} else {
    $a->beginTransaction(); $b->beginTransaction();
    try {
        $lock = $a->prepare('SELECT id_vaga_preenchida FROM vagas_preenchidas
            WHERE company_id = :company_id AND id_vaga_preenchida = :id FOR UPDATE');
        $lock->execute(['company_id' => $activeStay['company_id'], 'id' => $activeStay['id_vaga_preenchida']]);
        expectLockConflict(function () use ($b, $activeStay): void {
            $lock = $b->prepare('SELECT id_vaga_preenchida FROM vagas_preenchidas
                WHERE company_id = :company_id AND id_vaga_preenchida = :id FOR UPDATE');
            $lock->execute(['company_id' => $activeStay['company_id'], 'id' => $activeStay['id_vaga_preenchida']]);
        }, 'checkout');
        echo "Concurrent checkout lock passed\n";
    } finally { if ($b->inTransaction()) $b->rollBack(); if ($a->inTransaction()) $a->rollBack(); }
}

// Renovação: o contrato deve ser bloqueado antes de calcular o próximo período.
$contract = $a->query('SELECT company_id, id FROM monthly_contracts ORDER BY id LIMIT 1')
    ->fetch(PDO::FETCH_ASSOC);
if ($contract !== false) {
    $a->beginTransaction(); $b->beginTransaction();
    try {
        $lock = $a->prepare('SELECT id FROM monthly_contracts WHERE company_id = :company_id AND id = :id FOR UPDATE');
        $lock->execute(['company_id' => $contract['company_id'], 'id' => $contract['id']]);
        expectLockConflict(function () use ($b, $contract): void {
            $lock = $b->prepare('SELECT id FROM monthly_contracts WHERE company_id = :company_id AND id = :id FOR UPDATE');
            $lock->execute(['company_id' => $contract['company_id'], 'id' => $contract['id']]);
        }, 'renovação mensalista');
        echo "Concurrent monthly renewal lock passed\n";
    } finally { if ($b->inTransaction()) $b->rollBack(); if ($a->inTransaction()) $a->rollBack(); }
}
