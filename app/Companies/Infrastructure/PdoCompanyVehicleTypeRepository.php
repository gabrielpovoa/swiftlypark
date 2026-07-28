<?php

declare(strict_types=1);

namespace App\Companies\Infrastructure;

use PDO;

final class PdoCompanyVehicleTypeRepository
{
    public const TYPES = ['carro', 'moto', 'caminhao', 'app'];

    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return list<string> */
    public function enabledTypes(int $companyId): array
    {
        $statement = $this->connection->prepare(
            'SELECT vehicle_type
             FROM company_vehicle_types
             WHERE company_id = :company_id AND is_enabled = 1
             ORDER BY FIELD(vehicle_type, "carro", "moto", "caminhao", "app")'
        );
        $statement->execute(['company_id' => $companyId]);
        $types = $statement->fetchAll(PDO::FETCH_COLUMN);

        // Compatibilidade durante deploy: antes da migration, empresas ainda
        // sem configuração explícita continuam oferecendo todos os tipos.
        if ($types === [] && !$this->hasConfiguration($companyId)) {
            return self::TYPES;
        }

        return array_values(array_map('strval', $types));
    }

    public function isEnabled(int $companyId, string $vehicleType): bool
    {
        if (!in_array($vehicleType, self::TYPES, true)) {
            return false;
        }

        return in_array($vehicleType, $this->enabledTypes($companyId), true);
    }

    /** @param list<string> $enabledTypes */
    public function sync(int $companyId, array $enabledTypes): void
    {
        $enabled = array_fill_keys($enabledTypes, true);
        $statement = $this->connection->prepare(
            'INSERT INTO company_vehicle_types (company_id, vehicle_type, is_enabled)
             VALUES (:company_id, :vehicle_type, :is_enabled)
             ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled), updated_at = NOW(6)'
        );

        foreach (self::TYPES as $type) {
            $statement->execute([
                'company_id' => $companyId,
                'vehicle_type' => $type,
                'is_enabled' => isset($enabled[$type]) ? 1 : 0,
            ]);
        }
    }

    private function hasConfiguration(int $companyId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM company_vehicle_types WHERE company_id = :company_id LIMIT 1'
        );
        $statement->execute(['company_id' => $companyId]);

        return $statement->fetchColumn() !== false;
    }
}
