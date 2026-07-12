<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TransacaoRepository extends BaseRepository
{
    public function findByVacancy(int $vacancyId): array
    {
        $query = 'SELECT id_transacao, valor, payment_method, payment_date FROM transacoes WHERE id_vaga_preenchida = :vacancy_id';
        $parameters = ['vacancy_id' => $vacancyId];
        $this->applyTenantFilter($query, $parameters, 'company_id');

        $statement = $this->prepareTenantStatement($query, $parameters);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
