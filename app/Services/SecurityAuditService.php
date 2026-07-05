<?php

declare(strict_types=1);

namespace App\Services;

use App\Context\RequestIdentity;
use App\Exceptions\AuditLogException;
use App\Repositories\AuditLogRepository;
use JsonException;

final class SecurityAuditService
{
    public const UNAUTHORIZED_ACCESS_ATTEMPT =
        'UNAUTHORIZED_ACCESS_ATTEMPT';

    public function __construct(
        private AuditLogRepository $auditLogs,
        private RequestIdentity $identity
    ) {
    }

    public function recordDeniedAccess(
        string $permission,
        string $route,
        string $reason
    ): void {
        try {
            $context = json_encode([
                'requested_action' => $permission,
                'route' => $route,
                'reason' => $reason,
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new AuditLogException(
                'Falha ao serializar o evento de segurança.',
                $this->identity->requestId(),
                $exception
            );
        }

        $this->auditLogs->insert([
            'user_id' => $this->identity->userId(),
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
}
