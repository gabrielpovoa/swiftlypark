<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

final class AuditLogPresenter
{
    private const ENTITY_LABELS = [
        'vagas_preenchidas' => 'Ocupação',
        'vagas_disponiveis' => 'Vaga',
        'transacoes' => 'Pagamento',
        'financial_adjustments' => 'Ajuste financeiro',
        'authorization' => 'Segurança',
        'identity' => 'Gestão de identidade',
        'companies' => 'Empresa',
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
            'changes' => $this->changes($oldValues, $newValues),
            'display_date' => $this->displayDate($log['created_at']),
            'technical_json' => $log['new_values']
                ?? $log['old_values']
                ?? '{}',
        ];
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
            ];
        }

        return $changes;
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
            ? implode(', ', array_map('strval', $value))
            : (string) $value;
    }

    private function displayDate(string $date): string
    {
        return (new DateTimeImmutable($date, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
            ->format('d/m/Y \à\s H:i:s');
    }
}
