<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\AuditLogException;
use PDO;
use Throwable;

final class AuditLogRepository extends BaseRepository
{

    public function insert(array $entry): void
    {
        $entry += ['company_id' => null];
        $entry = array_intersect_key($entry, array_flip([
            'user_id',
            'company_id',
            'actor_email',
            'action',
            'entity',
            'entity_id',
            'old_values',
            'new_values',
            'ip_address',
            'request_id',
            'created_at',
        ]));

        try {
            $statement = $this->prepareSystemStatement(
                'INSERT INTO audit_logs (
                    user_id, company_id, actor_email, action, entity, entity_id,
                    old_values, new_values, ip_address, request_id, created_at
                 ) VALUES (
                    :user_id, :company_id, :actor_email, :action, :entity, :entity_id,
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

        $query = 'SELECT
                al.id, al.user_id, al.company_id, al.actor_email,
                u.nome AS actor_name, al.action, al.entity, al.entity_id,
                al.old_values, al.new_values, al.ip_address,
                al.request_id, al.created_at
             FROM audit_logs al
             LEFT JOIN usuario u ON u.id_usuario = al.user_id'
            . $where
            . ' ORDER BY ' . $orderBy
            . ' LIMIT ' . $limit;
        $this->applyTenantFilter($query, $parameters, 'al.company_id');

        $statement = $this->prepareTenantStatement($query, $parameters);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findActors(): array
    {
        $query = 'SELECT al.actor_email, MAX(u.nome) AS actor_name
             FROM audit_logs al
             LEFT JOIN usuario u ON u.id_usuario = al.user_id
             GROUP BY al.actor_email
             ORDER BY actor_name ASC, al.actor_email ASC';
        $parameters = [];
        $this->applyTenantFilter($query, $parameters, 'al.company_id');

        $statement = $this->prepareTenantStatement($query, $parameters);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findGlobalActors(): array
    {
        $statement = $this->queryGlobal(
            'SELECT al.actor_email, MAX(u.nome) AS actor_name
             FROM audit_logs al
             LEFT JOIN usuario u ON u.id_usuario = al.user_id
             GROUP BY al.actor_email
             ORDER BY actor_name ASC, al.actor_email ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findActorsByCompany(int $companyId): array
    {
        $statement = $this->prepareGlobalStatement(
            'SELECT al.actor_email, MAX(u.nome) AS actor_name
             FROM audit_logs al
             LEFT JOIN usuario u ON u.id_usuario = al.user_id
             WHERE al.company_id = :company_id
             GROUP BY al.actor_email
             ORDER BY actor_name ASC, al.actor_email ASC'
        );
        $statement->execute(['company_id' => $companyId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGlobalLogs(
        ?string $actorEmail = null,
        ?string $startAt = null,
        ?string $endAt = null,
        string $order = 'recent',
        int $limit = 200
    ): array
    {
        $limit = max(1, min($limit, 500));
        [$where, $parameters] = $this->filteredWhere($actorEmail, $startAt, $endAt);
        $orderBy = $order === 'user'
            ? 'al.actor_email ASC, al.created_at DESC'
            : 'al.created_at DESC';
        $statement = $this->prepareGlobalStatement(
            'SELECT
                al.id, al.user_id, al.company_id, al.actor_email,
                u.nome AS actor_name, al.action, al.entity, al.entity_id,
                al.old_values, al.new_values, al.ip_address,
                al.request_id, al.created_at
             FROM audit_logs al
             LEFT JOIN usuario u ON u.id_usuario = al.user_id
             ' . $where . '
             ORDER BY ' . $orderBy . '
             LIMIT ' . $limit
        );
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLogsByCompany(
        int $companyId,
        ?string $actorEmail = null,
        ?string $startAt = null,
        ?string $endAt = null,
        string $order = 'recent',
        int $limit = 200
    ): array
    {
        $limit = max(1, min($limit, 500));
        [$where, $parameters] = $this->filteredWhere($actorEmail, $startAt, $endAt);
        $parameters['company_id'] = $companyId;
        $where = $where === ''
            ? 'WHERE al.company_id = :company_id'
            : $where . ' AND al.company_id = :company_id';
        $orderBy = $order === 'user'
            ? 'al.actor_email ASC, al.created_at DESC'
            : 'al.created_at DESC';
        $statement = $this->prepareGlobalStatement(
            'SELECT
                al.id, al.user_id, al.company_id, al.actor_email,
                u.nome AS actor_name, al.action, al.entity, al.entity_id,
                al.old_values, al.new_values, al.ip_address,
                al.request_id, al.created_at
             FROM audit_logs al
             LEFT JOIN usuario u ON u.id_usuario = al.user_id
             ' . $where . '
             ORDER BY ' . $orderBy . '
             LIMIT ' . $limit
        );
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countSecurityEvents(
        string $action,
        int $userId,
        string $ipAddress,
        int $minutes = 10
    ): int {
        $threshold = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->modify('-' . max(1, $minutes) . ' minutes')
            ->format('Y-m-d H:i:s.u');

        $statement = $this->prepareGlobalStatement(
            'SELECT COUNT(*)
             FROM audit_logs
             WHERE action = :action
               AND user_id = :user_id
               AND ip_address = :ip_address
               AND created_at >= :threshold'
        );
        $statement->bindValue('action', $action);
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('ip_address', $ipAddress);
        $statement->bindValue('threshold', $threshold);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    private function filteredWhere(?string $actorEmail, ?string $startAt, ?string $endAt): array
    {
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

        return [
            $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions),
            $parameters,
        ];
    }
}
