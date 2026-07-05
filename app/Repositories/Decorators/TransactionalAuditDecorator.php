<?php

declare(strict_types=1);

namespace App\Repositories\Decorators;

use App\Services\AuditService;

final class TransactionalAuditDecorator
{
    public function __construct(private AuditService $auditService)
    {
    }

    public function created(
        string $entity,
        string|int $entityId,
        callable $loadNewValues
    ): void {
        $this->auditService->record(
            AuditService::CREATE,
            $entity,
            $entityId,
            null,
            $loadNewValues()
        );
    }

    public function updated(
        string $entity,
        string|int $entityId,
        array $oldValues,
        callable $loadNewValues
    ): void {
        $this->auditService->record(
            AuditService::UPDATE,
            $entity,
            $entityId,
            $oldValues,
            $loadNewValues()
        );
    }

    public function deleted(
        string $entity,
        string|int $entityId,
        array $oldValues
    ): void {
        $this->auditService->record(
            AuditService::DELETE,
            $entity,
            $entityId,
            $oldValues,
            null
        );
    }
}
