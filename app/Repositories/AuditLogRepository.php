<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\AuditLogException;
use PDO;
use Throwable;

final class AuditLogRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function insert(array $entry): void
    {
        try {
            $statement = $this->connection->prepare(
                'INSERT INTO audit_logs (
                    user_id, actor_email, action, entity, entity_id,
                    old_values, new_values, ip_address, request_id, created_at
                 ) VALUES (
                    :user_id, :actor_email, :action, :entity, :entity_id,
                    :old_values, :new_values, :ip_address, :request_id, :created_at
                 )'
            );
            $statement->execute($entry);
        } catch (Throwable $throwable) {
            throw new AuditLogException(
                'Falha ao persistir o registro de auditoria.',
                (string) $entry['request_id'],
                $throwable
            );
        }
    }
}
