<?php

declare(strict_types=1);

namespace App\Services;

use App\Context\RequestIdentity;
use App\Context\TenantContext;
use App\Exceptions\AuditLogException;
use App\Repositories\AuditLogRepository;
use JsonException;

final class AuditService
{
    public const CREATE = 'CREATE';
    public const UPDATE = 'UPDATE';
    public const DELETE = 'DELETE';

    private const ALLOWED_FIELDS = [
        'vagas_disponiveis' => [
            'id_vaga',
            'categoria',
            'status',
            'created_by',
            'updated_by',
        ],
        'vagas_preenchidas' => [
            'id_vaga_preenchida',
            'id_vaga',
            'hora_entrada',
            'hora_saida',
            'tempo_total',
            'nome_cliente',
            'telefone',
            'placa',
            'valor_pago',
            'tipo_veiculo',
            'created_by',
            'updated_by',
        ],
        'transacoes' => [
            'id_transacao',
            'id_vaga_preenchida',
            'valor',
            'payment_method',
            'data_transacao',
            'payment_date',
            'created_by',
            'updated_by',
        ],
    ];

    public function __construct(
        private AuditLogRepository $auditLogs,
        private RequestIdentity $identity,
        private ?TenantContext $tenantContext = null
    ) {
        $this->tenantContext ??= TenantContext::instance();
    }

    public function log(string $action, array $data): void
    {
        unset($data['company_id']);

        try {
            $this->auditLogs->insert([
                'user_id' => $this->identity->userId(),
                'company_id' => $this->tenantContext->getCompanyId(),
                'actor_email' => $this->identity->email(),
                'action' => $action,
                'entity' => (string) ($data['entity'] ?? 'system'),
                'entity_id' => (string) ($data['entity_id'] ?? $this->identity->userId()),
                'old_values' => $this->encodePayload($data['old_values'] ?? null),
                'new_values' => $this->encodePayload($data['new_values'] ?? null),
                'ip_address' => $this->identity->ipAddress(),
                'request_id' => $this->identity->requestId(),
                'created_at' => $this->identity
                    ->requestedAt()
                    ->format('Y-m-d H:i:s.u'),
            ]);
        } catch (JsonException $exception) {
            throw new AuditLogException(
                'Falha ao serializar os dados de auditoria.',
                $this->identity->requestId(),
                $exception
            );
        }
    }

    public function record(
        string $action,
        string $entity,
        string|int $entityId,
        ?array $oldValues,
        ?array $newValues
    ): void {
        if (!in_array($action, [self::CREATE, self::UPDATE, self::DELETE], true)) {
            throw new AuditLogException(
                'Ação de auditoria inválida.',
                $this->identity->requestId()
            );
        }

        if (!isset(self::ALLOWED_FIELDS[$entity])) {
            throw new AuditLogException(
                'Entidade não configurada para auditoria.',
                $this->identity->requestId()
            );
        }

        $oldValues = $this->sanitize($entity, $oldValues);
        $newValues = $this->sanitize($entity, $newValues);

        if ($action === self::UPDATE) {
            [$oldValues, $newValues] = $this->changedValues(
                $oldValues ?? [],
                $newValues ?? []
            );

            if ($newValues === []) {
                return;
            }
        }

        try {
            $this->auditLogs->insert([
                'user_id' => $this->identity->userId(),
                'company_id' => $this->tenantContext->getCompanyId(),
                'actor_email' => $this->identity->email(),
                'action' => $action,
                'entity' => $entity,
                'entity_id' => (string) $entityId,
                'old_values' => $oldValues === null
                    ? null
                    : json_encode($oldValues, JSON_THROW_ON_ERROR),
                'new_values' => $newValues === null
                    ? null
                    : json_encode($newValues, JSON_THROW_ON_ERROR),
                'ip_address' => $this->identity->ipAddress(),
                'request_id' => $this->identity->requestId(),
                'created_at' => $this->identity
                    ->requestedAt()
                    ->format('Y-m-d H:i:s.u'),
            ]);
        } catch (JsonException $exception) {
            throw new AuditLogException(
                'Falha ao serializar os dados de auditoria.',
                $this->identity->requestId(),
                $exception
            );
        }
    }

    private function sanitize(string $entity, ?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return array_intersect_key(
            $values,
            array_flip(self::ALLOWED_FIELDS[$entity])
        );
    }

    private function encodePayload(mixed $payload): ?string
    {
        if ($payload === null || is_string($payload)) {
            return $payload;
        }

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    private function changedValues(array $oldValues, array $newValues): array
    {
        $changedOld = [];
        $changedNew = [];

        foreach ($newValues as $field => $newValue) {
            $oldValue = $oldValues[$field] ?? null;

            if ((string) $oldValue === (string) $newValue) {
                continue;
            }

            $changedOld[$field] = $oldValue;
            $changedNew[$field] = $newValue;
        }

        return [$changedOld, $changedNew];
    }
}
