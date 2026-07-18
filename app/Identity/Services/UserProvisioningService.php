<?php

declare(strict_types=1);

namespace App\Identity\Services;

use App\Context\RequestIdentity;
use App\Contracts\PasswordRecoveryMailerInterface;
use App\Identity\Events\UserCreatedEvent;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\PasswordGeneratorService;
use App\Services\PasswordRecoveryMailer;
use DomainException;
use PDO;

final class UserProvisioningService
{
    public function __construct(
        private PDO $connection,
        private ?RequestIdentity $actor = null,
        private ?AuditService $audit = null,
        private ?NotificationService $notifications = null,
        private ?PasswordGeneratorService $passwords = null,
        private ?PasswordRecoveryMailerInterface $mailer = null
    ) {
        $this->notifications ??= new NotificationService();
        $this->passwords ??= new PasswordGeneratorService();
        $this->mailer ??= new PasswordRecoveryMailer();
    }

    public function create(
        string $name,
        string $email,
        int $companyId,
        int $roleId
    ): int {
        $name = trim($name);
        $email = strtolower(trim($email));

        $this->assertValidName($name);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new DomainException('Nome e e-mail válidos são obrigatórios.');
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
            $temporaryPassword = $this->passwords->temporary();
            $hash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
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

            $this->dispatch(new UserCreatedEvent($userId, $email, $companyId, $roleId));
            $this->mailer->sendTemporaryPassword($email, $temporaryPassword);

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


    public function linkExistingUserToCompany(
        int $userId,
        int $companyId,
        int $roleId
    ): void {
        if ($userId <= 0 || $companyId <= 0 || $roleId <= 0) {
            throw new DomainException('Usuário, empresa e papel são obrigatórios.');
        }

        if ($this->actor !== null
            && $this->actor->userId() === $userId
            && !in_array('super-admin', $this->actor->roleSlugs(), true)) {
            throw new DomainException(
                'Você não pode alterar o próprio vínculo de empresa por esta tela.'
            );
        }

        $user = $this->activeUser($userId);
        $company = $this->company($companyId);
        $role = $this->role($roleId);
        $this->assertCanAssignRole($role);

        $this->connection->beginTransaction();

        try {
            $membership = $this->membershipForUpdate($userId, $companyId);

            if ($membership === null) {
                $statement = $this->connection->prepare(
                    'INSERT INTO company_user (company_id, user_id, role_id, created_at)
                     VALUES (:company_id, :user_id, :role_id, NOW(6))'
                );
                $statement->execute([
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'role_id' => $roleId,
                ]);
            } else {
                $statement = $this->connection->prepare(
                    'UPDATE company_user
                     SET role_id = :role_id
                     WHERE company_id = :company_id AND user_id = :user_id'
                );
                $statement->execute([
                    'role_id' => $roleId,
                    'company_id' => $companyId,
                    'user_id' => $userId,
                ]);
            }

            $this->consolidateMembership($userId, $companyId);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $throwable;
        }

        $this->audit?->log($membership === null ? 'CREATE' : 'UPDATE', [
            'entity' => 'company_user',
            'entity_id' => $companyId . ':' . $userId,
            'new_values' => [
                'message' => sprintf(
                    'Gestor %s vinculou usuário %s à empresa %s com papel %s.',
                    $this->actor?->email() ?? 'CLI',
                    $user['email'],
                    $company['name'],
                    $role['slug']
                ),
                'actor_user_id' => $this->actor?->userId(),
                'actor_email' => $this->actor?->email(),
                'target_user_id' => $userId,
                'target_email' => $user['email'],
                'company_id' => $companyId,
                'company_name' => $company['name'],
                'role_id' => $roleId,
                'role_slug' => $role['slug'],
                'previous_role_id' => $membership['role_id'] ?? null,
            ],
        ]);
    }

    public function removeCompanyAccess(int $userId, int $companyId): void
    {
        if ($userId <= 0 || $companyId <= 0) {
            throw new DomainException('Usuário e empresa são obrigatórios.');
        }

        $user = $this->activeUser($userId);
        $company = $this->company($companyId);

        if ($this->actor !== null
            && $this->actor->userId() === $userId
            && $this->activeMembershipsCount($userId) <= 1) {
            throw new DomainException('Você não pode remover seu último acesso a empresa ativa.');
        }

        $this->connection->beginTransaction();

        try {
            $membership = $this->membershipForUpdate($userId, $companyId);
            if ($membership === null) {
                throw new DomainException('Esse usuário não possui vínculo com a empresa selecionada.');
            }

            $statement = $this->connection->prepare(
                'DELETE FROM company_user
                 WHERE company_id = :company_id AND user_id = :user_id'
            );
            $statement->execute([
                'company_id' => $companyId,
                'user_id' => $userId,
            ]);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $throwable;
        }

        $this->audit?->log('DELETE', [
            'entity' => 'company_user',
            'entity_id' => $companyId . ':' . $userId,
            'old_values' => [
                'message' => sprintf(
                    'Gestor %s removeu o acesso de %s à empresa %s.',
                    $this->actor?->email() ?? 'CLI',
                    $user['email'],
                    $company['name']
                ),
                'target_user_id' => $userId,
                'target_email' => $user['email'],
                'company_id' => $companyId,
                'company_name' => $company['name'],
                'role_id' => $membership['role_id'] ?? null,
            ],
        ]);
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

    private function assertValidName(string $name): void
    {
        if (strlen($name) < 2 || preg_match('/[\pL]/u', $name) !== 1) {
            throw new DomainException(
                'O nome deve ter ao menos 2 caracteres e conter letras.'
            );
        }
    }

    private function dispatch(object $event): void
    {
        // Hook central para plugar um EventBus sem espalhar acoplamento pela camada de domínio.
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

    private function activeUser(int $userId): array
    {
        $statement = $this->connection->prepare(
            'SELECT id_usuario, nome, email
             FROM usuario
             WHERE id_usuario = :id AND deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            throw new DomainException('Usuário ativo inválido.');
        }

        return $user;
    }

    private function membershipForUpdate(int $userId, int $companyId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, company_id, user_id, role_id
             FROM company_user
             WHERE user_id = :user_id AND company_id = :company_id
             FOR UPDATE'
        );
        $statement->execute([
            'user_id' => $userId,
            'company_id' => $companyId,
        ]);
        $membership = $statement->fetch(PDO::FETCH_ASSOC);

        return $membership === false ? null : $membership;
    }

    private function consolidateMembership(int $userId, int $companyId): void
    {
        $statement = $this->connection->prepare(
            'SELECT id
             FROM company_user
             WHERE user_id = :user_id AND company_id = :company_id
             ORDER BY role_id IS NULL ASC, id ASC'
        );
        $statement->execute([
            'user_id' => $userId,
            'company_id' => $companyId,
        ]);
        $ids = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));

        if (count($ids) <= 1) {
            return;
        }

        $keepId = array_shift($ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $delete = $this->connection->prepare(
            'DELETE FROM company_user
             WHERE id IN (' . $placeholders . ')
               AND user_id = ?
               AND company_id = ?
               AND id <> ?'
        );
        $delete->execute([...$ids, $userId, $companyId, $keepId]);
    }

    private function activeMembershipsCount(int $userId): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*)
             FROM company_user cu
             INNER JOIN companies c ON c.id = cu.company_id AND c.deleted_at IS NULL
             WHERE cu.user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
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


    private function roleBySlug(string $slug): array
    {
        $statement = $this->connection->prepare(
            'SELECT id, slug, display_priority
             FROM roles
             WHERE slug = :slug AND is_active = 1
             LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);
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

        if (!array_intersect(['master', 'admin'], $this->actor->roleSlugs())) {
            throw new DomainException('Apenas usuários MASTER ou ADMIN podem provisionar acessos.');
        }

        if (in_array($role['slug'], ['master', 'super-admin'], true)) {
            throw new DomainException('Apenas SUPER-ADMIN pode conceder papel equivalente ou superior.');
        }
    }
}
