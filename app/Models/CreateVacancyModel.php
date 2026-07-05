<?php

declare(strict_types=1);

namespace App\Models;

use App\Context\IdentityContext;
use App\Repositories\AuditLogRepository;
use App\Repositories\Decorators\TransactionalAuditDecorator;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Transactions\TransactionManager;
use Config\Database;
use PDO;
use Throwable;

final class CreateVacancyModel
{
    private PDO $db;
    private TransactionalAuditDecorator $audit;
    private TransactionManager $transactions;

    public function __construct()
    {
        $this->db = (new Database())->connect();
        $this->audit = new TransactionalAuditDecorator(
            new AuditService(
                new AuditLogRepository($this->db),
                IdentityContext::current()
            )
        );
        $this->transactions = new TransactionManager($this->db);
    }

    public function createVacancy(string $category, int $amount): bool
    {
        try {
            (new AuthorizationService(IdentityContext::current()))
                ->check('vacancy.create');

            $this->transactions->run(function () use ($category, $amount) {
                $statement = $this->db->prepare(
                    'INSERT INTO vagas_disponiveis (
                        categoria, created_by, updated_by
                     ) VALUES (
                        :categoria, :created_by, :updated_by
                     )'
                );
                $userId = IdentityContext::current()->userId();

                for ($index = 0; $index < $amount; $index++) {
                    $statement->execute([
                        'categoria' => $category,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                    $id = (int) $this->db->lastInsertId();

                    $this->audit->created(
                        'vagas_disponiveis',
                        $id,
                        fn (): array => $this->findVacancy($id)
                    );
                }
            });

            return true;
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());

            return false;
        }
    }

    private function findVacancy(int $id): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM vagas_disponiveis WHERE id_vaga = :id'
        );
        $statement->execute(['id' => $id]);

        return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
