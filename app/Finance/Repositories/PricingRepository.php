<?php

declare(strict_types=1);

namespace App\Finance\Repositories;

use PDO;

final class PricingRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function findTariff(int $companyId, string $vehicleType): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT company_id, tipo_veiculo, valor_base, valor_adicional,
                    tolerancia_minutos, frequencia_adicional
             FROM tarifarios
             WHERE company_id = :company_id
               AND tipo_veiculo = :tipo_veiculo
             LIMIT 1'
        );
        $statement->execute([
            'company_id' => $companyId,
            'tipo_veiculo' => $vehicleType,
        ]);
        $tariff = $statement->fetch(PDO::FETCH_ASSOC);

        return $tariff === false ? null : $tariff;
    }

    public function isMonthlyCompany(int $companyId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT is_mensalista FROM companies WHERE id = :company_id LIMIT 1'
        );
        $statement->execute(['company_id' => $companyId]);
        $value = $statement->fetchColumn();

        return $value !== false && (int) $value === 1;
    }
}
