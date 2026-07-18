<?php

declare(strict_types=1);

namespace App\Parking\Infrastructure;

use App\Repositories\BaseRepository;

use PDO;

final class VagasRepository extends BaseRepository
{
    public function findAvailable(): array
    {
        $query = 'SELECT id_vaga_disponivel, status, tipo_veiculo FROM vagas_disponiveis';
        $parameters = [];
        $this->applyTenantFilter($query, $parameters, 'company_id');

        $statement = $this->prepareTenantStatement($query, $parameters);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findFilled(): array
    {
        $query = 'SELECT id_vaga_preenchida, placa, hora_entrada FROM vagas_preenchidas';
        $parameters = [];
        $this->applyTenantFilter($query, $parameters, 'company_id');

        $statement = $this->prepareTenantStatement($query, $parameters);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

