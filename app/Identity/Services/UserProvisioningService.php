<?php

declare(strict_types=1);

namespace App\Identity\Services;

use App\Context\RequestIdentity;
use App\Services\AuditService;
use App\Services\NotificationService;
use DomainException;
use PDO;

final class UserProvisioningService
{
    public function __construct(
        private PDO $connection,
        private ?RequestIdentity $actor = null,
        private ?AuditService $audit = null,
        private ?NotificationService $notifications = null
    ) {
        $this->notifications ??= new NotificationService();
    }

    public function create(
        string $name,
        string $email,
        string $password,
        int $companyId,
        int $roleId
    ): int {
        $name = trim($name);
        $email = strtolower(trim($email));

        if ($name === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new DomainException('Nome e e-mail válidos são obrigatórios.');
        }

        if (strlen($password) < 12) {
            throw new DomainException('A senha inicial deve ter ao menos 12 caracteres.');
        }

        if ($companyId <= 0 || $roleId <= 0) {
            throw new DomainException('Empresa e papel são obrigatórios.');
        }

        $company = $this->company($companyId);
        $role = $this->role($roleId);
        $this->assertCanAssignRole($role);
        $this->assertEmailAvailable($email);

        $this->connection->beginTransaction();

        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $login = $this->connection->prepare(
                'INSERT INTO login (email, senha) VALUES (:email, :password_hash)'
            );
            $login->execute([
                'email' => $email,
                'password_hash' => $hash,
            ]);
            $loginId = (int) $this->connection->lastInsertId();

            $user = $this->connection->prepare(
                'INSERT INTO usuario (
                    id_login, nome, email, senha_hash, password_reset_required
                 ) VALUES (
                    :login_id, :name, :email, :password_hash, 1
                 )'
            );
            $user->execute([
                'login_id' => $loginId,
                'name' => $name,
                'email' => $email,
                'password_hash' => $hash,
            ]);
            $userId = (int) $this->connection->lastInsertId();

            $userRole = $this->connection->prepare(
                'INSERT INTO user_roles (user_id, role_id, created_by)
                 VALUES (:user_id, :role_id, :created_by)'
            );
            $userRole->execute([
                'user_id' => $userId,
                'role_id' => $roleId,
                'created_by' => $this->actor?->userId(),
            ]);

            $companyUser = $this->connection->prepare(
                'INSERT INTO company_user (company_id, user_id, role_id, created_at)
                 VALUES (:company_id, :user_id, :role_id, NOW())'
            );
            $companyUser->execute([
                'company_id' => $companyId,
                'user_id' => $userId,
                'role_id' => $roleId,
            ]);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $throwable;
        }

        $this->notifications->accountCreated($email, $name, (string) $company['name']);
        $this->audit?->log('CREATE', [
            'entity' => 'identity',
            'entity_id' => $userId,
            'new_values' => [
                'message' => sprintf(
                    'Master %s criou usuário %s na empresa %s com papel %s.',
                    $this->actor?->email() ?? 'CLI',
                    $email,
                    $company['name'],
                    $role['slug']
                ),
                'target_user_id' => $userId,
                'target_email' => $email,
                'company_id' => $companyId,
                'company_name' => $company['name'],
                'role_id' => $roleId,
                'role_slug' => $role['slug'],
            ],
        ]);

        return $userId;
    }

    public function createCompany(string $name, string $slug, ?string $logoPath = null): int
    {
        $name = trim($name);
        $slug = strtolower(trim($slug));

        if ($name === '' || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
            throw new DomainException('Nome e slug válido são obrigatórios.');
        }

        $existing = $this->connection->prepare(
            'SELECT 1 FROM companies WHERE slug = :slug LIMIT 1'
        );
        $existing->execute(['slug' => $slug]);

        if ($existing->fetchColumn() !== false) {
            throw new DomainException('Já existe uma empresa com esse slug.');
        }

        $statement = $this->connection->prepare(
            'INSERT INTO companies (name, slug, logo_path, created_at, updated_at)
             VALUES (:name, :slug, :logo_path, NOW(), NOW())'
        );
        $statement->execute([
            'name' => $name,
            'slug' => $slug,
            'logo_path' => $logoPath,
        ]);
        $companyId = (int) $this->connection->lastInsertId();

        $this->audit?->log('CREATE', [
            'entity' => 'companies',
            'entity_id' => $companyId,
            'new_values' => [
                'message' => sprintf(
                    'Master %s criou empresa %s.',
                    $this->actor?->email() ?? 'CLI',
                    $name
                ),
                'company_id' => $companyId,
                'company_name' => $name,
                'slug' => $slug,
                'logo_path' => $logoPath,
            ],
        ]);

        return $companyId;
    }

    private function assertEmailAvailable(string $email): void
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM usuario WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);

        if ($statement->fetchColumn() !== false) {
            throw new DomainException('Já existe um usuário com esse e-mail.');
        }
    }

    private function company(int $companyId): array
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, slug, logo_path FROM companies
             WHERE id = :id AND deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute(['id' => $companyId]);
        $company = $statement->fetch(PDO::FETCH_ASSOC);

        if ($company === false) {
            throw new DomainException('Empresa inválida.');
        }

        return $company;
    }

    private function role(int $roleId): array
    {
        $statement = $this->connection->prepare(
            'SELECT id, slug, display_priority
             FROM roles
             WHERE id = :id AND is_active = 1
             LIMIT 1'
        );
        $statement->execute(['id' => $roleId]);
        $role = $statement->fetch(PDO::FETCH_ASSOC);

        if ($role === false) {
            throw new DomainException('Papel inválido.');
        }

        return $role;
    }

    private function assertCanAssignRole(array $role): void
    {
        if ($this->actor === null) {
            return;
        }

        if (in_array('super-admin', $this->actor->roleSlugs(), true)) {
            return;
        }

        if (!in_array('master', $this->actor->roleSlugs(), true)) {
            throw new DomainException('Apenas usuários MASTER podem provisionar acessos.');
        }

        if (in_array($role['slug'], ['master', 'super-admin'], true)) {
            throw new DomainException('Apenas SUPER-ADMIN pode conceder papel equivalente ou superior.');
        }
    }
}
