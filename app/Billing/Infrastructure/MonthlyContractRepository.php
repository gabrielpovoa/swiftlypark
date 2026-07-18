<?php

declare(strict_types=1);

namespace App\Billing\Infrastructure;

use PDO;

final class MonthlyContractRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function activeForVehicle(int $companyId, string $plate, string $vehicleType): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM monthly_contracts
             WHERE company_id = :company_id
               AND vehicle_plate = :vehicle_plate
               AND vehicle_type = :vehicle_type
               AND status = "ACTIVE"
               AND starts_at <= CURRENT_DATE()
               AND expires_at >= CURRENT_DATE()
             LIMIT 1'
        );
        $statement->execute([
            'company_id' => $companyId,
            'vehicle_plate' => strtoupper(trim($plate)),
            'vehicle_type' => $vehicleType,
        ]);
        $contract = $statement->fetch(PDO::FETCH_ASSOC);

        return $contract === false ? null : $contract;
    }

    public function findForUpdate(int $companyId, int $contractId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM monthly_contracts
             WHERE id = :contract_id AND company_id = :company_id
             LIMIT 1 FOR UPDATE'
        );
        $statement->execute(['contract_id' => $contractId, 'company_id' => $companyId]);
        $contract = $statement->fetch(PDO::FETCH_ASSOC);

        return $contract === false ? null : $contract;
    }

    public function listForCompany(int $companyId): array
    {
        $statement = $this->connection->prepare(
            'SELECT mc.*,
                    (SELECT MAX(p.payment_date) FROM monthly_contract_payments p
                     WHERE p.contract_id = mc.id) AS last_payment_at
             FROM monthly_contracts mc
             WHERE mc.company_id = :company_id
             ORDER BY mc.status = "ACTIVE" DESC, mc.expires_at DESC, mc.customer_name'
        );
        $statement->execute(['company_id' => $companyId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

