<?php

declare(strict_types=1);

namespace App\Services;

use App\Context\RequestIdentity;
use App\Context\TenantContext;
use App\Exceptions\AuditLogException;
use App\Repositories\AuditLogRepository;
use JsonException;

final class SecurityAuditService
{
    public const UNAUTHORIZED_ACCESS_ATTEMPT =
        'UNAUTHORIZED_ACCESS_ATTEMPT';
    public const CROSS_TENANT_ACCESS_ATTEMPT =
        'CROSS_TENANT_ACCESS_ATTEMPT';
    public const SYSTEMATIC_TENANT_SCAN_DETECTED =
        'SYSTEMATIC_TENANT_SCAN_DETECTED';

    private const TENANT_SCAN_THRESHOLD = 5;

    public function __construct(
        private AuditLogRepository $auditLogs,
        private RequestIdentity $identity,
        private ?TenantContext $tenantContext = null
    ) {
        $this->tenantContext ??= TenantContext::instance();
    }

    public function recordDeniedAccess(
        string $permission,
        string $route,
        string $reason
    ): void {
        try {
            $payload = [
                'role_slug' => $this->identity->primaryRoleSlug(),
                'role_slugs' => $this->identity->roleSlugs(),
                'requested_action' => $permission,
                'route' => $route,
                'reason' => $reason,
            ];
            $supportContext = $this->supportContext();
            if ($supportContext !== null) {
                $payload['_support_context'] = $supportContext;
            }
            $context = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new AuditLogException(
                'Falha ao serializar o evento de segurança.',
                $this->identity->requestId(),
                $exception
            );
        }

        $this->auditLogs->insert([
            'user_id' => $this->identity->userId(),
            'company_id' => $this->tenantContext->getCompanyId(),
            'actor_email' => $this->identity->email(),
            'action' => self::UNAUTHORIZED_ACCESS_ATTEMPT,
            'entity' => 'authorization',
            'entity_id' => (string) $this->identity->userId(),
            'old_values' => null,
            'new_values' => $context,
            'ip_address' => $this->identity->ipAddress(),
            'request_id' => $this->identity->requestId(),
            'created_at' => $this->identity
                ->requestedAt()
                ->format('Y-m-d H:i:s.u'),
        ]);
    }

    public function recordCrossTenantAccess(
        string $route,
        int $attemptedCompanyId,
        string $reason
    ): void {
        $this->recordSecurityEvent(
            self::CROSS_TENANT_ACCESS_ATTEMPT,
            'tenant',
            (string) $attemptedCompanyId,
            [
                'severity' => 'CRITICAL',
                'route' => $route,
                'user_id' => $this->identity->userId(),
                'ip_address' => $this->identity->ipAddress(),
                'active_company_id' => $this->tenantContext->getCompanyId(),
                'attempted_company_id' => $attemptedCompanyId,
                'reason' => $reason,
                'error_code' => \App\Exceptions\SecurityCriticalException::CODE_CROSS_TENANT_ACCESS,
            ]
        );

        if ($this->auditLogs->countSecurityEvents(
            self::CROSS_TENANT_ACCESS_ATTEMPT,
            $this->identity->userId(),
            $this->identity->ipAddress()
        ) >= self::TENANT_SCAN_THRESHOLD) {
            $this->recordSecurityEvent(
                self::SYSTEMATIC_TENANT_SCAN_DETECTED,
                'tenant_intrusion_detection',
                (string) $this->identity->userId(),
                [
                    'severity' => 'CRITICAL',
                    'route' => $route,
                    'user_id' => $this->identity->userId(),
                    'ip_address' => $this->identity->ipAddress(),
                    'attempted_company_id' => $attemptedCompanyId,
                    'threshold' => self::TENANT_SCAN_THRESHOLD,
                    'window_minutes' => 10,
                    'alert_target' => 'MASTER',
                ]
            );
        }
    }

    public function recordCriticalQueryBlocked(
        string $route,
        string $securityCode,
        string $reason
    ): void {
        $this->recordSecurityEvent(
            self::CROSS_TENANT_ACCESS_ATTEMPT,
            'fail_closed_guard',
            $securityCode,
            [
                'severity' => 'CRITICAL',
                'route' => $route,
                'user_id' => $this->identity->userId(),
                'ip_address' => $this->identity->ipAddress(),
                'active_company_id' => $this->tenantContext->getCompanyId(),
                'error_code' => $securityCode,
                'reason' => $reason,
            ]
        );
    }

    private function recordSecurityEvent(
        string $action,
        string $entity,
        string $entityId,
        array $context
    ): void {
        try {
            $supportContext = $this->supportContext();
            if ($supportContext !== null) {
                $context['_support_context'] = $supportContext;
            }
            $payload = json_encode($context, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new AuditLogException(
                'Falha ao serializar o evento de segurança.',
                $this->identity->requestId(),
                $exception
            );
        }

        $this->auditLogs->insert([
            'user_id' => $this->identity->userId(),
            'company_id' => $this->tenantContext->getCompanyId(),
            'actor_email' => $this->identity->email(),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'old_values' => null,
            'new_values' => $payload,
            'ip_address' => $this->identity->ipAddress(),
            'request_id' => $this->identity->requestId(),
            'created_at' => $this->identity
                ->requestedAt()
                ->format('Y-m-d H:i:s.u'),
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
