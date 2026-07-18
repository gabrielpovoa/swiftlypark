<?php

declare(strict_types=1);

namespace App\Finance\Services;

use App\Context\RequestIdentity;
use App\Context\TenantContext;
use App\Repositories\AuditLogRepository;
use DateTimeImmutable;
use DateTimeZone;
use JsonException;

final class FinancialAuditService
{
    public function __construct(
        private AuditLogRepository $auditLogs,
        private RequestIdentity $identity,
        private ?TenantContext $tenantContext = null
    ) {
        $this->tenantContext ??= TenantContext::instance();
    }

    public function recordAdjustment(
        int $adjustmentId,
        int $transactionId,
        string $type,
        float $amount,
        string $reason
    ): void {
        $payload = [
            'adjustment_id' => $adjustmentId,
            'transaction_id' => $transactionId,
            'adjustment_type' => $type,
            'amount' => $amount,
            'reason' => $reason,
        ];
        $supportContext = $this->supportContext();
        if ($supportContext !== null) {
            $payload['_support_context'] = $supportContext;
        }

        try {
            $newValues = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \RuntimeException(
                'Falha ao serializar o ajuste financeiro para auditoria.',
                0,
                $exception
            );
        }

        $this->auditLogs->insert([
            'user_id' => $this->identity->userId(),
            'company_id' => $this->tenantContext->getCompanyId(),
            'actor_email' => $this->identity->email(),
            'action' => 'FINANCIAL_ADJUSTMENT',
            'entity' => 'financial_adjustments',
            'entity_id' => (string) $adjustmentId,
            'old_values' => null,
            'new_values' => $newValues,
            'ip_address' => $this->identity->ipAddress(),
            'request_id' => $this->identity->requestId(),
            'created_at' => (new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC')
            ))->format('Y-m-d H:i:s.u'),
        ]);
    }

    private function supportContext(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            return null;
        }

        $support = $_SESSION['support_impersonation'] ?? null;
        if (!is_array($support) || empty($support['company_id'])) {
            return null;
        }

        return [
            'support_mode' => true,
            'real_user_id' => $this->identity->userId(),
            'company_id' => (int) $support['company_id'],
            'company_name' => (string) ($support['company_name'] ?? ''),
            'simulated_role' => (string) ($support['simulated_role'] ?? ''),
            'simulated_role_label' => (string) ($support['simulated_role_label'] ?? ''),
            'extra_permissions' => is_array($support['extra_permissions'] ?? null)
                ? array_values($support['extra_permissions'])
                : [],
        ];
    }
}
