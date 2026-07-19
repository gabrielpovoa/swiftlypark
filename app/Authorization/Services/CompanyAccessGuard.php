<?php

declare(strict_types=1);

namespace App\Authorization\Services;

use App\Context\RequestIdentity;
use App\Exceptions\ForbiddenException;
use PDO;

final class CompanyAccessGuard
{
    public function __construct(
        private readonly PDO $connection,
        private readonly RequestIdentity $identity
    ) {
    }

    public function isSuperAdmin(): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id AND r.is_active = 1
             WHERE ur.user_id = :user_id AND r.slug = "super-admin" LIMIT 1'
        );
        $statement->execute(['user_id' => $this->identity->userId()]);

        return $statement->fetchColumn() !== false;
    }

    public function assertCanManage(int $companyId): void
    {
        if ($companyId < 1) {
            throw new ForbiddenException('company.manage', 'Empresa inválida.');
        }

        if ($this->isSuperAdmin()) {
            return;
        }

        if (!in_array('admin', $this->identity->roleSlugs(), true)) {
            throw new ForbiddenException('company.manage', 'Apenas administradores podem alterar a empresa.');
        }

        $statement = $this->connection->prepare(
            'SELECT 1 FROM company_user cu
             INNER JOIN companies c ON c.id = cu.company_id AND c.deleted_at IS NULL
             INNER JOIN roles r ON r.id = cu.role_id AND r.slug = "admin" AND r.is_active = 1
             WHERE cu.user_id = :user_id AND cu.company_id = :company_id LIMIT 1'
        );
        $statement->execute([
            'user_id' => $this->identity->userId(),
            'company_id' => $companyId,
        ]);

        if ($statement->fetchColumn() === false) {
            throw new ForbiddenException(
                'company.manage',
                'Você não possui acesso administrativo a esta empresa.'
            );
        }
    }
}
