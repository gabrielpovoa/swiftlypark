<?php

declare(strict_types=1);

namespace App\Identity\Presentation;

use App\Context\IdentityContext;
use App\Authorization\Services\CompanyAccessGuard;
use App\Authorization\Services\UserAccessGuard;
use App\Identity\Application\UserProvisioningService;
use App\Repositories\AuditLogRepository;
use App\Exceptions\ForbiddenException;
use App\Services\AuditService;
use App\Services\PasswordGeneratorService;
use App\Services\QueuedPasswordRecoveryMailer;
use Config\Database;
use Core\Controller;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use Throwable;

final class AdminUserProvisioningController extends Controller
{
    public function index(): void
    {
        $this->startSession();
        $this->assertGovernanceAdmin();

        $connection = (new Database())->connect();
        $filters = $this->userFilters();

        $this->setView('Admin/provisioning', [
            'title' => 'Governança SaaS - SwiftlyPark',
            'companies' => $this->companies($connection),
            'roles' => $this->assignableRoles($connection),
            'permissions' => $this->permissions($connection),
            'activeUsers' => $this->activeUsers($connection),
            'users' => $this->users($connection, $filters),
            'userFilters' => $filters,
            'activeUsersCount' => $this->activeUsersCount($connection),
            'passwordResetUsersCount' => $this->passwordResetUsersCount($connection),
            'csrfToken' => $this->csrfToken(),
            'success' => $_SESSION['admin_success'] ?? null,
            'error' => $_SESSION['admin_error'] ?? null,
        ]);

        unset($_SESSION['admin_success'], $_SESSION['admin_error']);
    }

    public function sendTemporaryPassword(): void
    {
        $this->startSession();

        if (!$this->hasValidAdminCsrf()) {
            $_SESSION['admin_error'] = 'A sessão expirou. Tente novamente.';
            $this->redirect();
        }

        $payload = $this->payload();
        $connection = (new Database())->connect();

        try {
            $this->assertGovernanceAdmin();

            $userId = (int) ($payload['user_id'] ?? 0);
            if ($userId <= 0) {
                throw new DomainException('Selecione um usuário válido.');
            }

            (new UserAccessGuard($connection, IdentityContext::current()))
                ->assertCanManage($userId);

            $user = $this->userForPasswordReset($connection, $userId);
            if ($user === null || $user['deleted_at'] !== null) {
                throw new DomainException('Usuário indisponível para redefinição.');
            }

            $temporaryPassword = (new PasswordGeneratorService())->temporary();
            $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);

            $connection->beginTransaction();
            try {
                $updateUser = $connection->prepare(
                    'UPDATE usuario
                     SET senha_hash = :password_hash,
                         password_reset_required = 1
                     WHERE id_usuario = :user_id'
                );
                $updateUser->execute([
                    'password_hash' => $passwordHash,
                    'user_id' => $userId,
                ]);

                if ($updateUser->rowCount() !== 1) {
                    throw new DomainException('Não foi possível atualizar a senha do usuário.');
                }

                $updateLogin = $connection->prepare(
                    'UPDATE login
                     SET senha = :password_hash
                     WHERE id_login = :login_id'
                );
                $updateLogin->execute([
                    'password_hash' => $passwordHash,
                    'login_id' => $user['id_login'],
                ]);

                $mailer = QueuedPasswordRecoveryMailer::fromConnection($connection);
                $mailer->sendTemporaryPassword($user['email'], $temporaryPassword);

                $connection->commit();
                $_SESSION['admin_success'] = 'Senha temporária adicionada à fila de envio.';
            } catch (Throwable $throwable) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }

                throw $throwable;
            }
        } catch (ForbiddenException $exception) {
            throw $exception;
        } catch (DomainException $exception) {
            $_SESSION['admin_error'] = $exception->getMessage();
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            $_SESSION['admin_error'] = 'Não foi possível enfileirar a senha temporária.';
        }

        $this->redirect();
    }

    public function createUser(): void
    {
        $this->respond(function (): array {
            $this->assertValidFormRequest();
            $payload = $this->payload();
            $connection = (new Database())->connect();
            $identity = IdentityContext::current();
            $service = new UserProvisioningService(
                $connection,
                $identity,
                new AuditService(
                    new AuditLogRepository($connection),
                    $identity
                )
            );

            $userId = $service->create(
                (string) ($payload['name'] ?? ''),
                (string) ($payload['email'] ?? ''),
                (int) ($payload['company_id'] ?? 0),
                (int) ($payload['role_id'] ?? 0)
            );

            return ['user_id' => $userId];
        }, 'Usuário provisionado com sucesso.');
    }

    public function linkExistingUser(): void
    {
        $this->respond(function (): array {
            $this->assertValidFormRequest();
            $payload = $this->payload();
            $connection = (new Database())->connect();
            $identity = IdentityContext::current();
            $service = new UserProvisioningService(
                $connection,
                $identity,
                new AuditService(
                    new AuditLogRepository($connection),
                    $identity
                )
            );

            $service->linkExistingUserToCompany(
                (int) ($payload['user_id'] ?? 0),
                (int) ($payload['company_id'] ?? 0),
                (int) ($payload['role_id'] ?? 0)
            );

            return ['linked' => true];
        }, 'Usuário vinculado à empresa com sucesso.');
    }

    public function removeCompanyAccess(): void
    {
        $this->respond(function (): array {
            $this->assertValidFormRequest();
            $payload = $this->payload();
            $connection = (new Database())->connect();
            $identity = IdentityContext::current();
            $service = new UserProvisioningService(
                $connection,
                $identity,
                new AuditService(
                    new AuditLogRepository($connection),
                    $identity
                )
            );

            $service->removeCompanyAccess(
                (int) ($payload['user_id'] ?? 0),
                (int) ($payload['company_id'] ?? 0)
            );

            return ['removed' => true];
        }, 'Acesso à empresa removido com sucesso.');
    }

    public function syncCompanyPermissions(): void
    {
        $this->respond(function (): array {
            $this->assertValidFormRequest();
            $payload = $this->payload();
            $connection = (new Database())->connect();
            $userId = (int) ($payload['user_id'] ?? 0);
            $companyId = (int) ($payload['company_id'] ?? 0);
            $permissionIds = $payload['permissions'] ?? [];

            (new CompanyAccessGuard($connection, IdentityContext::current()))
                ->assertCanManage($companyId);

            $this->syncTenantPermissionOverrides(
                $connection,
                $userId,
                $companyId,
                is_array($permissionIds) ? $permissionIds : []
            );

            return ['updated' => true];
        }, 'Permissões do perfil na empresa atualizadas com sucesso.');
    }





    private function userForPasswordReset(\PDO $connection, int $userId): ?array
    {
        $statement = $connection->prepare(
            'SELECT id_usuario, id_login, email, deleted_at
             FROM usuario
             WHERE id_usuario = :user_id
             LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);
        $user = $statement->fetch(\PDO::FETCH_ASSOC);

        return $user === false ? null : $user;
    }

    private function companies(\PDO $connection): array
    {
        return $connection->query(
            'SELECT
                c.id, c.name, c.slug, c.logo_path, COUNT(cu.user_id) AS users_count
             FROM companies c
             LEFT JOIN company_user cu ON cu.company_id = c.id
             WHERE c.deleted_at IS NULL
             GROUP BY c.id, c.name, c.slug, c.logo_path
             ORDER BY c.name ASC'
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function permissions(\PDO $connection): array
    {
        return $connection->query(
            'SELECT id, slug, name
             FROM permissions
             WHERE is_active = 1
             ORDER BY name ASC, slug ASC'
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function activeUsers(\PDO $connection): array
    {
        return $connection->query(
            'SELECT
                u.id_usuario,
                u.nome,
                u.email,
                GROUP_CONCAT(
                    DISTINCT CONCAT(c.id, \'::\', c.name, \'::\', c.slug)
                    ORDER BY c.name
                    SEPARATOR \'||\'
                ) AS company_access
             FROM usuario u
             LEFT JOIN (
                SELECT user_id, company_id, MAX(role_id) AS role_id
                FROM company_user
                GROUP BY user_id, company_id
             ) cu ON cu.user_id = u.id_usuario
             LEFT JOIN roles r ON r.id = cu.role_id AND r.is_active = 1
             LEFT JOIN companies c ON c.id = cu.company_id AND c.deleted_at IS NULL
                AND r.id IS NOT NULL
             WHERE u.deleted_at IS NULL
             GROUP BY u.id_usuario, u.nome, u.email
             ORDER BY u.nome ASC, u.email ASC'
        )->fetchAll(\PDO::FETCH_ASSOC);
    }



    private function assignableRoles(\PDO $connection): array
    {
        $roles = IdentityContext::current()->roleSlugs();
        $canAssignPrivileged = in_array('super-admin', $roles, true);
        $where = $canAssignPrivileged
            ? 'WHERE is_active = 1'
            : "WHERE is_active = 1 AND slug <> 'super-admin'";

        return $connection->query(
            'SELECT id, slug, name, label
             FROM roles ' . $where . '
             ORDER BY display_priority ASC, name ASC'
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function users(\PDO $connection, array $filters): array
    {
        $where = ['u.deleted_at IS NULL'];
        $parameters = [];

        if ($filters['query'] !== '') {
            $where[] = '(u.nome LIKE :user_name_query OR u.email LIKE :user_email_query)';
            $parameters['user_name_query'] = '%' . $filters['query'] . '%';
            $parameters['user_email_query'] = '%' . $filters['query'] . '%';
        }

        if ($filters['company_id'] !== null) {
            $where[] = 'cu.company_id = :company_id';
            $parameters['company_id'] = $filters['company_id'];
        }

        if ($filters['password_reset_required']) {
            $where[] = 'u.password_reset_required = 1';
        }

        $limit = $filters['is_filtered'] ? 20 : 3;
        $statement = $connection->prepare(
            'SELECT
                u.id_usuario,
                u.nome,
                u.email,
                u.password_reset_required,
                GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR \', \') AS company_name,
                GROUP_CONCAT(DISTINCT c.slug ORDER BY c.slug SEPARATOR \', \') AS company_slug,
                GROUP_CONCAT(DISTINCT r.slug ORDER BY r.slug SEPARATOR \', \') AS role_slug,
                GROUP_CONCAT(
                    DISTINCT CONCAT(
                        c.id,
                        \'::\',
                        c.name,
                        \'::\',
                        COALESCE(r.slug, \'sem papel\'),
                        \'::\',
                        COALESCE(extra_permissions.permission_ids, \'\'),
                        \'::\',
                        COALESCE(r.label, r.name, r.slug, \'Sem papel\')
                    )
                    ORDER BY c.name
                    SEPARATOR \'||\'
                ) AS company_access
             FROM usuario u
             LEFT JOIN (
                SELECT user_id, company_id, MAX(role_id) AS role_id
                FROM company_user
                GROUP BY user_id, company_id
             ) cu ON cu.user_id = u.id_usuario
             LEFT JOIN companies c ON c.id = cu.company_id
             LEFT JOIN roles r ON r.id = cu.role_id
             LEFT JOIN (
                SELECT
                    user_id,
                    company_id,
                    GROUP_CONCAT(permission_id ORDER BY permission_id SEPARATOR \',\') AS permission_ids
                FROM company_user_permissions
                GROUP BY user_id, company_id
             ) extra_permissions
                ON extra_permissions.user_id = u.id_usuario
               AND extra_permissions.company_id = c.id
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY u.id_usuario, u.nome, u.email, u.password_reset_required
             ORDER BY u.id_usuario DESC
             LIMIT ' . $limit
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue(
                ':' . $key,
                $value,
                $key === 'company_id' ? \PDO::PARAM_INT : \PDO::PARAM_STR
            );
        }
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function activeUsersCount(\PDO $connection): int
    {
        return (int) $connection
            ->query('SELECT COUNT(*) FROM usuario WHERE deleted_at IS NULL')
            ->fetchColumn();
    }

    private function syncTenantPermissionOverrides(
        \PDO $connection,
        int $userId,
        int $companyId,
        array $permissionIds
    ): void {
        if ($userId <= 0 || $companyId <= 0) {
            throw new DomainException('Selecione usuário e empresa válidos.');
        }

        $membership = $connection->prepare(
            'SELECT 1
             FROM company_user cu
             INNER JOIN usuario u ON u.id_usuario = cu.user_id AND u.deleted_at IS NULL
             INNER JOIN companies c ON c.id = cu.company_id AND c.deleted_at IS NULL
             WHERE cu.user_id = :user_id
               AND cu.company_id = :company_id
               AND cu.role_id IS NOT NULL
             LIMIT 1'
        );
        $membership->execute([
            'user_id' => $userId,
            'company_id' => $companyId,
        ]);

        if ($membership->fetchColumn() === false) {
            throw new DomainException('Este usuário não possui vínculo ativo com a empresa selecionada.');
        }

        $permissionIds = array_values(array_unique(array_filter(
            array_map('intval', $permissionIds),
            static fn (int $permissionId): bool => $permissionId > 0
        )));
        $before = $this->companyPermissionIds($connection, $userId, $companyId);

        $connection->beginTransaction();
        try {
            $delete = $connection->prepare(
                'DELETE FROM company_user_permissions
                 WHERE user_id = :user_id AND company_id = :company_id'
            );
            $delete->execute([
                'user_id' => $userId,
                'company_id' => $companyId,
            ]);

            $insert = $connection->prepare(
                'INSERT INTO company_user_permissions (
                    user_id, company_id, permission_id, granted_by
                 )
                 SELECT :user_id, :company_id, id, :granted_by
                 FROM permissions
                 WHERE id = :permission_id AND is_active = 1'
            );

            foreach ($permissionIds as $permissionId) {
                $insert->execute([
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'permission_id' => $permissionId,
                    'granted_by' => IdentityContext::current()->userId(),
                ]);
            }

            $after = $this->companyPermissionIds($connection, $userId, $companyId);
            $this->auditCompany($connection, 'USER_PERMISSIONS_UPDATED', $companyId, [
                'scope' => 'company_user',
                'target_user_id' => $userId,
                'target_company_id' => $companyId,
                'permissions_added' => $this->permissionSlugs(
                    $connection,
                    array_values(array_diff($after, $before))
                ),
                'permissions_removed' => $this->permissionSlugs(
                    $connection,
                    array_values(array_diff($before, $after))
                ),
            ]);

            $connection->commit();
        } catch (Throwable $throwable) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $throwable;
        }
    }

    private function companyPermissionIds(\PDO $connection, int $userId, int $companyId): array
    {
        $statement = $connection->prepare(
            'SELECT permission_id
             FROM company_user_permissions
             WHERE user_id = :user_id AND company_id = :company_id'
        );
        $statement->execute([
            'user_id' => $userId,
            'company_id' => $companyId,
        ]);

        return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    private function permissionSlugs(\PDO $connection, array $permissionIds): array
    {
        if ($permissionIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($permissionIds), '?'));
        $statement = $connection->prepare(
            'SELECT slug
             FROM permissions
             WHERE id IN (' . $placeholders . ')
             ORDER BY slug ASC'
        );
        $statement->execute(array_values($permissionIds));

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function passwordResetUsersCount(\PDO $connection): int
    {
        return (int) $connection
            ->query('SELECT COUNT(*) FROM usuario WHERE deleted_at IS NULL AND password_reset_required = 1')
            ->fetchColumn();
    }

    private function userFilters(): array
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $companyId = filter_var(
            $_GET['company_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $passwordResetRequired = (string) ($_GET['password_reset_required'] ?? '') === '1';

        return [
            'query' => substr($query, 0, 120),
            'company_id' => $companyId === false ? null : (int) $companyId,
            'password_reset_required' => $passwordResetRequired,
            'is_filtered' => $query !== ''
                || $companyId !== false
                || $passwordResetRequired,
        ];
    }








    private function auditCompany(
        \PDO $connection,
        string $action,
        int $companyId,
        array $payload
    ): void {
        $identity = IdentityContext::current();
        (new AuditLogRepository($connection))->insert([
            'user_id' => $identity->userId(),
            'company_id' => $companyId,
            'actor_email' => $identity->email(),
            'action' => $action,
            'entity' => 'companies',
            'entity_id' => (string) $companyId,
            'old_values' => null,
            'new_values' => json_encode($payload, JSON_THROW_ON_ERROR),
            'ip_address' => $identity->ipAddress(),
            'request_id' => $identity->requestId(),
            'created_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                ->format('Y-m-d H:i:s.u'),
        ]);
    }

    private function respond(callable $action, string $successMessage): void
    {
        if ($this->expectsJson()) {
            $this->jsonResponse($action);

            return;
        }

        $this->startSession();

        try {
            $this->assertGovernanceAdmin();
            $action();
            $_SESSION['admin_success'] = $successMessage;
        } catch (DomainException $exception) {
            $_SESSION['admin_error'] = $exception->getMessage();
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            $_SESSION['admin_error'] = 'Não foi possível concluir o provisionamento.';
        }

        $companyId = filter_var(
            $_POST['return_company_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($companyId !== false) {
            $this->redirectCompanyPricing((int) $companyId);
        }

        $this->redirect();
    }

    private function jsonResponse(callable $action): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $this->assertGovernanceAdmin();
            http_response_code(201);
            echo json_encode($action(), JSON_THROW_ON_ERROR);
        } catch (ForbiddenException $exception) {
            http_response_code(403);
            echo json_encode(['error' => $exception->getMessage()]);
        } catch (DomainException $exception) {
            http_response_code(422);
            echo json_encode(['error' => $exception->getMessage()]);
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Não foi possível concluir o provisionamento.']);
        }
    }

    private function expectsJson(): bool
    {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');

        return str_contains($contentType, 'application/json')
            || str_contains($accept, 'application/json');
    }

    private function assertValidFormRequest(): void
    {
        if ($this->expectsJson()) {
            return;
        }

        $this->startSession();

        if (!$this->hasValidAdminCsrf()) {
            throw new DomainException('A sessão expirou. Tente novamente.');
        }
    }

    private function hasValidAdminCsrf(): bool
    {
        return isset($_SESSION['admin_csrf'], $_POST['csrf_token'])
            && hash_equals($_SESSION['admin_csrf'], (string) $_POST['csrf_token']);
    }

    private function csrfToken(): string
    {
        if (!isset($_SESSION['admin_csrf'])) {
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['admin_csrf'];
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function redirect(): never
    {
        header('Location: /admin');
        exit;
    }


    private function redirectCompanyPricing(int $companyId): never
    {
        if ($companyId < 1) {
            $this->redirectCompanies();
        }

        header('Location: /admin/companies/company_id=' . $companyId);
        exit;
    }

    private function assertGovernanceAdmin(): void
    {
        $roles = IdentityContext::current()->roleSlugs();
        if (!in_array('super-admin', $roles, true)
            && !in_array('admin', $roles, true)) {
            throw new ForbiddenException(
                'admin.provision',
                'Apenas usuários ADMIN ou SUPER-ADMIN podem provisionar acessos.'
            );
        }
    }

    private function payload(): array
    {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);

            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

}
