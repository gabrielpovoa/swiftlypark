<?php

declare(strict_types=1);

namespace App\Authorization\Services;

use App\Context\RequestIdentity;
use App\Context\TenantContext;
use App\Exceptions\ForbiddenException;
use PDO;

final class UserAccessGuard
{
    public function __construct(
        private readonly PDO $connection,
        private readonly RequestIdentity $identity,
        private readonly ?TenantContext $tenantContext = null
    ) {
    }

    public function assertCanManage(int $targetUserId): void
    {
        if ($targetUserId === $this->identity->userId() || $this->isGlobalSuperAdmin($this->identity->userId())) {
            return;
        }

        if ($targetUserId < 1 || $this->isGlobalSuperAdmin($targetUserId)) {
            throw new ForbiddenException(
                'identity.manage',
                'O perfil do Super-Admin é restrito à administração global.'
            );
        }

        $companyId = ($this->tenantContext ?? TenantContext::instance())->getCompanyId();
        if ($companyId === null || !in_array('admin', $this->identity->roleSlugs(), true)) {
            throw new ForbiddenException('identity.manage', 'Usuário fora do seu escopo administrativo.');
        }

        $statement = $this->connection->prepare(
            'SELECT 1 FROM company_user
             WHERE user_id = :user_id AND company_id = :company_id LIMIT 1'
        );
        $statement->execute(['user_id' => $targetUserId, 'company_id' => $companyId]);

        if ($statement->fetchColumn() === false) {
            throw new ForbiddenException('identity.manage', 'Usuário fora do seu escopo administrativo.');
        }
    }

    private function isGlobalSuperAdmin(int $userId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id AND r.slug = "super-admin" AND r.is_active = 1
             WHERE ur.user_id = :user_id LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchColumn() !== false;
    }
}
