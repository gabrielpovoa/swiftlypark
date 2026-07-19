<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

final class AuditLogPresenter
{
    private const EVENT_LABELS = [
        'GLOBAL_DASHBOARD_ACCESS' => 'Acesso ao painel global',
        'IMPERSONATION_STARTED' => 'Modo suporte iniciado',
        'SUPPORT_PROFILE_CHANGED' => 'Perfil de suporte alterado',
        'IMPERSONATION_ENDED' => 'Modo suporte encerrado',
        'COMPANY_PRICING_UPDATED' => 'Configuração de cobrança atualizada',
        'MONTHLY_CONTRACT_CREATED' => 'Contrato mensalista criado',
        'MONTHLY_CONTRACT_RENEWED' => 'Contrato mensalista renovado',
        'MONTHLY_CONTRACT_CANCELLED' => 'Contrato mensalista cancelado',
    ];

    private const ENTITY_LABELS = [
        'vagas_preenchidas' => 'Ocupação',
        'vagas_disponiveis' => 'Vaga',
        'transacoes' => 'Pagamento',
        'financial_adjustments' => 'Ajuste financeiro',
        'authorization' => 'Segurança',
        'identity' => 'Gestão de identidade',
        'companies' => 'Empresa',
        'support_impersonation' => 'Modo suporte',
    ];

    private const ENTITY_MESSAGES = [
        'vagas_preenchidas' => [
            'subject' => 'A ocupação',
            'CREATE' => 'foi registrada',
            'UPDATE' => 'foi atualizada',
            'DELETE' => 'foi excluída',
        ],
        'vagas_disponiveis' => [
            'subject' => 'A vaga',
            'CREATE' => 'foi criada',
            'UPDATE' => 'foi atualizada',
            'DELETE' => 'foi excluída',
        ],
        'transacoes' => [
            'subject' => 'O pagamento',
            'CREATE' => 'foi registrado',
            'UPDATE' => 'foi atualizado',
            'DELETE' => 'foi excluído',
        ],
    ];

    private const FIELD_LABELS = [
        'status' => 'Status da vaga',
        'placa' => 'Placa',
        'nome_cliente' => 'Cliente',
        'telefone' => 'Telefone',
        'hora_entrada' => 'Horário de entrada',
        'hora_saida' => 'Horário de saída',
        'tempo_total' => 'Tempo total',
        'valor_pago' => 'Valor pago',
        'valor' => 'Valor',
        'tipo_veiculo' => 'Tipo do veículo',
        'categoria' => 'Categoria',
        'requested_action' => 'Permissão solicitada',
        'route' => 'Página solicitada',
        'reason' => 'Motivo',
        'role_slug' => 'Papel do usuário',
        'payment_method' => 'Forma de pagamento',
        'transaction_id' => 'Transação',
        'adjustment_type' => 'Tipo do ajuste',
        'amount' => 'Valor do ajuste',
        'reason' => 'Justificativa',
        'target_user_id' => 'Usuário alterado',
        'target_email' => 'E-mail alterado',
        'permissions_added' => 'Permissões adicionadas',
        'permissions_removed' => 'Permissões removidas',
        'company_id' => 'Empresa',
        'company_name' => 'Nome da empresa',
        'company_slug' => 'Slug da empresa',
        'old_name' => 'Nome anterior',
        'old_slug' => 'Slug anterior',
        'new_name' => 'Novo nome',
        'new_slug' => 'Novo slug',
        'revoked_company_memberships' => 'Vínculos revogados',
        'revoked_user_ids' => 'Usuários revogados',
        '_support_context' => 'Contexto de suporte',
        'event' => 'Evento',
        'target_company_id' => 'Empresa acessada',
        'target_company_name' => 'Nome da empresa',
        'super_admin_user_id' => 'Super-Admin',
        'simulated_role' => 'Perfil simulado',
        'simulated_role_label' => 'Perfil simulado',
        'extra_permissions' => 'Permissões extras',
        'user_agent' => 'Navegador',
    ];

    public function present(array $log): array
    {
        $oldValues = $this->decode($log['old_values'] ?? null);
        $newValues = $this->decode($log['new_values'] ?? null);

        return [
            ...$log,
            'actor_display' => $log['actor_name'] ?: $log['actor_email'],
            'action_label' => $this->actionLabel($log['action']),
            'entity_label' => self::ENTITY_LABELS[$log['entity']]
                ?? ucfirst(str_replace('_', ' ', $log['entity'])),
            'description' => $this->description($log, $oldValues, $newValues),
            'event_label' => $this->eventLabel($log, $newValues),
            'changes' => $this->changes($oldValues, $newValues),
            'display_date' => $this->displayDate($log['created_at']),
            'technical_details' => $this->technicalDetails($log, $newValues),
            'technical_json' => $this->prettyTechnicalJson($oldValues, $newValues),
        ];
    }

    private function eventLabel(array $log, array $newValues): string
    {
        $event = (string) ($newValues['event'] ?? '');

        if (isset(self::EVENT_LABELS[$event])) {
            return self::EVENT_LABELS[$event];
        }

        return sprintf('%s de %s', $this->actionLabel($log['action']), self::ENTITY_LABELS[$log['entity']]
            ?? ucfirst(str_replace('_', ' ', $log['entity'])));
    }

    private function decode(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function actionLabel(string $action): string
    {
        return match ($action) {
            'CREATE' => 'Criação',
            'UPDATE' => 'Alteração',
            'DELETE' => 'Exclusão',
            'UNAUTHORIZED_ACCESS_ATTEMPT' => 'Acesso negado',
            'ACCESS_REVOKED' => 'Acesso revogado',
            'USER_PERMISSIONS_UPDATED' => 'Permissões alteradas',
            'FINANCIAL_ADJUSTMENT' => 'Ajuste financeiro',
            default => 'Evento',
        };
    }

    private function description(
        array $log,
        array $oldValues,
        array $newValues
    ): string {
        if ($log['action'] === 'UNAUTHORIZED_ACCESS_ATTEMPT') {
            return sprintf(
                'Tentou acessar “%s” sem a permissão necessária.',
                $newValues['route'] ?? 'uma área protegida'
            );
        }

        if ($log['action'] === 'ACCESS_REVOKED') {
            return sprintf(
                'O acesso de %s foi revogado.',
                $newValues['target_email'] ?? ('usuário #' . $log['entity_id'])
            );
        }

        if ($log['action'] === 'USER_PERMISSIONS_UPDATED') {
            return sprintf(
                'As permissões extras de %s foram atualizadas.',
                $newValues['target_email'] ?? ('usuário #' . $log['entity_id'])
            );
        }

        if ($log['entity'] === 'companies') {
            return match ($log['action']) {
                'CREATE' => sprintf(
                    'A empresa %s foi criada.',
                    $newValues['company_name'] ?? ('#' . $log['entity_id'])
                ),
                'UPDATE' => sprintf(
                    'A empresa %s foi atualizada.',
                    $newValues['new_name'] ?? ('#' . $log['entity_id'])
                ),
                'DELETE' => sprintf(
                    'A empresa %s foi inativada.',
                    $newValues['company_name'] ?? ('#' . $log['entity_id'])
                ),
                default => sprintf('A empresa #%s recebeu um evento.', $log['entity_id']),
            };
        }

        if ($log['entity'] === 'support_impersonation') {
            return match ($newValues['event'] ?? '') {
                'IMPERSONATION_STARTED' => sprintf(
                    'Modo suporte iniciado na empresa %s, visualizando como %s.',
                    $newValues['target_company_name'] ?? ('#' . $log['entity_id']),
                    $newValues['simulated_role_label'] ?? $newValues['simulated_role'] ?? 'perfil simulado'
                ),
                'SUPPORT_PROFILE_CHANGED' => sprintf(
                    'Perfil de suporte alterado para %s na empresa %s.',
                    $newValues['simulated_role_label'] ?? $newValues['simulated_role'] ?? 'perfil simulado',
                    $newValues['target_company_name'] ?? ('#' . $log['entity_id'])
                ),
                'IMPERSONATION_ENDED' => sprintf(
                    'Modo suporte encerrado na empresa %s.',
                    $newValues['target_company_name'] ?? ('#' . $log['entity_id'])
                ),
                default => sprintf('Modo suporte atualizado para a empresa #%s.', $log['entity_id']),
            };
        }

        if (($newValues['event'] ?? '') === 'GLOBAL_DASHBOARD_ACCESS') {
            return 'O painel consolidado de todas as empresas foi acessado.';
        }

        if (($newValues['event'] ?? '') === 'COMPANY_PRICING_UPDATED') {
            return sprintf(
                'Os dados cadastrais e a configuração de cobrança da empresa %s foram atualizados.',
                $log['company_name'] ?? ('#' . $log['entity_id'])
            );
        }

        if (str_starts_with((string) ($newValues['event'] ?? ''), 'MONTHLY_CONTRACT_')) {
            return match ($newValues['event']) {
                'MONTHLY_CONTRACT_CREATED' => sprintf(
                    'O contrato mensalista #%s foi criado%s.',
                    $newValues['contract_id'] ?? $log['entity_id'],
                    isset($newValues['vehicle_plate']) ? ' para o veículo ' . $newValues['vehicle_plate'] : ''
                ),
                'MONTHLY_CONTRACT_RENEWED' => sprintf(
                    'O contrato mensalista #%s foi renovado até %s.',
                    $newValues['contract_id'] ?? $log['entity_id'],
                    $newValues['period_end'] ?? 'a nova data de vencimento'
                ),
                'MONTHLY_CONTRACT_CANCELLED' => sprintf(
                    'O contrato mensalista #%s foi cancelado.',
                    $newValues['contract_id'] ?? $log['entity_id']
                ),
                default => 'Um contrato mensalista foi atualizado.',
            };
        }

        if ($log['action'] === 'FINANCIAL_ADJUSTMENT') {
            return sprintf(
                'Um ajuste financeiro foi registrado na transação #%s.',
                $newValues['transaction_id'] ?? $log['entity_id']
            );
        }

        $message = self::ENTITY_MESSAGES[$log['entity']] ?? [
            'subject' => 'O registro',
            'CREATE' => 'foi criado',
            'UPDATE' => 'foi atualizado',
            'DELETE' => 'foi excluído',
        ];
        $reference = $newValues['placa']
            ?? $oldValues['placa']
            ?? ('#' . $log['entity_id']);

        return sprintf(
            '%s %s %s.',
            $message['subject'],
            $reference,
            $message[$log['action']] ?? 'recebeu um novo evento'
        );
    }

    private function changes(array $oldValues, array $newValues): array
    {
        $changes = [];
        $fields = array_unique([
            ...array_keys($oldValues),
            ...array_keys($newValues),
        ]);

        foreach ($fields as $field) {
            if (in_array($field, [
                'id_vaga',
                'id_vaga_preenchida',
                'id_transacao',
                'created_by',
                'updated_by',
                'role_slugs',
                'event',
                'user_agent',
                '_support_context',
            ], true)) {
                continue;
            }

            $old = $oldValues[$field] ?? null;
            $new = $newValues[$field] ?? null;

            if ($old === $new) {
                continue;
            }

            $changes[] = [
                'label' => self::FIELD_LABELS[$field]
                    ?? ucfirst(str_replace('_', ' ', $field)),
                'before' => $this->formatValue($field, $old),
                'after' => $this->formatValue($field, $new),
                'has_before' => array_key_exists($field, $oldValues),
                'has_after' => array_key_exists($field, $newValues),
            ];
        }

        return $changes;
    }

    private function technicalDetails(array $log, array $newValues): array
    {
        $companyId = $newValues['target_company_id']
            ?? $newValues['company_id']
            ?? $log['company_id']
            ?? null;
        $companyName = $newValues['target_company_name']
            ?? $newValues['company_name']
            ?? $log['company_name']
            ?? null;
        $route = $newValues['route'] ?? $this->routeFromEntityId((string) $log['entity_id']);

        return [
            ['label' => 'Ação técnica', 'value' => $newValues['event'] ?? $log['action']],
            ['label' => 'Página acessada', 'value' => $route],
            ['label' => 'Responsável', 'value' => $log['actor_name'] ?: $log['actor_email']],
            ['label' => 'E-mail', 'value' => $log['actor_email']],
            ['label' => 'Empresa', 'value' => $companyName],
            ['label' => 'ID da empresa', 'value' => $companyId],
            ['label' => 'Endereço IP', 'value' => $log['ip_address'] ?? null],
            ['label' => 'Navegador', 'value' => $newValues['user_agent'] ?? null],
            ['label' => 'ID da requisição', 'value' => $log['request_id'] ?? null],
            ['label' => 'Registro afetado', 'value' => sprintf('%s #%s', $log['entity'], $log['entity_id'])],
        ];
    }

    private function routeFromEntityId(string $entityId): ?string
    {
        if ($entityId === '' || !str_contains($entityId, '/')) {
            return null;
        }

        return '/' . ltrim($entityId, '/');
    }

    private function prettyTechnicalJson(array $oldValues, array $newValues): string
    {
        $payload = [];
        if ($oldValues !== []) {
            $payload['antes'] = $oldValues;
        }
        if ($newValues !== []) {
            $payload['depois'] = $newValues;
        }

        return (string) json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    private function formatValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'Não informado';
        }

        if (in_array($field, ['valor', 'valor_pago', 'amount'], true)) {
            return 'R$ ' . number_format((float) $value, 2, ',', '.');
        }

        if ($field === 'adjustment_type') {
            return match ($value) {
                'REFUND' => 'Estorno',
                'CORRECTION' => 'Correção',
                default => ucfirst(strtolower((string) $value)),
            };
        }

        if ($field === 'payment_method') {
            return match ($value) {
                'PIX' => 'Pix',
                'CARD' => 'Cartão',
                'CASH' => 'Dinheiro',
                'UNKNOWN' => 'Não informado',
                default => (string) $value,
            };
        }

        if ($field === 'status') {
            return match ($value) {
                'livre' => 'Livre',
                'reservada' => 'Ocupada',
                default => ucfirst((string) $value),
            };
        }

        return is_array($value)
            ? $this->formatArrayValue($value)
            : (string) $value;
    }

    private function formatArrayValue(array $value): string
    {
        if ($value === []) {
            return 'Nenhum';
        }

        if (array_is_list($value)) {
            return implode(', ', array_map(
                fn (mixed $item): string => is_array($item)
                    ? $this->formatArrayValue($item)
                    : (string) $item,
                $value
            ));
        }

        $labels = [];
        foreach ($value as $key => $item) {
            $label = self::FIELD_LABELS[(string) $key]
                ?? ucfirst(str_replace('_', ' ', (string) $key));
            $labels[] = sprintf(
                '%s: %s',
                $label,
                is_array($item) ? $this->formatArrayValue($item) : (string) $item
            );
        }

        return implode(' · ', $labels);
    }

    private function displayDate(string $date): string
    {
        return (new DateTimeImmutable($date, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
            ->format('d/m/Y \à\s H:i:s');
    }
}
