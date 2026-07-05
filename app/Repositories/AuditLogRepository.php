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

    public function findFiltered(
        ?string $actorEmail,
        ?string $startAt,
        ?string $endAt,
        string $order = 'recent',
        int $limit = 100
    ): array
    {
        $limit = max(1, min($limit, 200));
        $conditions = [];
        $parameters = [];

        if ($actorEmail !== null) {
            $conditions[] = 'al.actor_email = :actor_email';
            $parameters['actor_email'] = $actorEmail;
        }

        if ($startAt !== null) {
            $conditions[] = 'al.created_at >= :start_at';
            $parameters['start_at'] = $startAt;
        }

        if ($endAt !== null) {
            $conditions[] = 'al.created_at < :end_at';
            $parameters['end_at'] = $endAt;
        }

        $where = $conditions === []
            ? ''
            : ' WHERE ' . implode(' AND ', $conditions);
        $orderBy = $order === 'user'
            ? 'al.actor_email ASC, al.created_at DESC'
            : 'al.created_at DESC';

        $statement = $this->connection->prepare(
            'SELECT
                al.id, al.user_id, al.actor_email, u.nome AS actor_name,
                al.action, al.entity, al.entity_id, al.old_values,
                al.new_values, al.ip_address, al.request_id, al.created_at
             FROM audit_logs al
             LEFT JOIN usuario u ON u.id_usuario = al.user_id'
            . $where
            . ' ORDER BY ' . $orderBy
            . ' LIMIT ' . $limit
        );
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findActors(): array
    {
        $statement = $this->connection->query(
            'SELECT al.actor_email, MAX(u.nome) AS actor_name
             FROM audit_logs al
             LEFT JOIN usuario u ON u.id_usuario = al.user_id
             GROUP BY al.actor_email
             ORDER BY actor_name ASC, al.actor_email ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
