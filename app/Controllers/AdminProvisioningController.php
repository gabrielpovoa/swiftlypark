<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Context\IdentityContext;
use App\Identity\Services\UserProvisioningService;
use App\Repositories\AuditLogRepository;
use App\Exceptions\ForbiddenException;
use App\Services\AuditService;
use Config\Database;
use Core\Controller;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use Throwable;

final class AdminProvisioningController extends Controller
{
    public function index(): void
    {
        $this->startSession();
        $this->assertMaster();

        $connection = (new Database())->connect();
        $filters = $this->userFilters();

        $this->setView('Admin/provisioning', [
            'title' => 'Governança SaaS - SwiftlyPark',
            'companies' => $this->companies($connection),
            'roles' => $this->assignableRoles($connection),
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
                (string) ($payload['password'] ?? ''),
                (int) ($payload['company_id'] ?? 0),
                (int) ($payload['role_id'] ?? 0)
            );

            return ['user_id' => $userId];
        }, 'Usuário provisionado com sucesso.');
    }

    public function createCompany(): void
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

            $name = (string) ($payload['name'] ?? '');
            $slug = (string) ($payload['slug'] ?? '');
            if (trim($name) === ''
                || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', strtolower(trim($slug))) !== 1) {
                throw new DomainException('Nome e slug válido são obrigatórios.');
            }

            $logoPath = $this->storeCompanyLogo($_FILES['logo'] ?? null);

            $companyId = $service->createCompany(
                $name,
                $slug,
                $logoPath
            );

            return ['company_id' => $companyId];
        }, 'Empresa criada com sucesso.');
    }

    public function companiesIndex(): void
    {
        $this->startSession();
        $this->assertMaster();

        $connection = (new Database())->connect();
        $filters = $this->companyFilters();

        $this->setView('Admin/companies', [
            'title' => 'Empresas - Governança SaaS',
            'companies' => $this->companyDirectory($connection, $filters),
            'companyFilters' => $filters,
            'companiesCount' => $this->companiesCount($connection),
            'activeUsersCount' => $this->activeUsersCount($connection),
            'passwordResetUsersCount' => $this->passwordResetUsersCount($connection),
            'csrfToken' => $this->csrfToken(),
            'success' => $_SESSION['admin_success'] ?? null,
            'error' => $_SESSION['admin_error'] ?? null,
        ]);

        unset($_SESSION['admin_success'], $_SESSION['admin_error']);
    }

    public function updateCompany(): void
    {
        $this->executeCompanyAction(function (\PDO $connection, array $payload): void {
            $companyId = (int) ($payload['company_id'] ?? 0);
            $name = trim((string) ($payload['name'] ?? ''));
            $slug = strtolower(trim((string) ($payload['slug'] ?? '')));
            $logoPath = $this->storeCompanyLogo($_FILES['logo'] ?? null);

            if ($companyId <= 0
                || $name === ''
                || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
                throw new DomainException('Empresa, nome e slug válido são obrigatórios.');
            }

            $connection->beginTransaction();

            try {
                $company = $this->companyForUpdate($connection, $companyId);
                if ($company === null || $company['deleted_at'] !== null) {
                    throw new DomainException('Empresa indisponível para edição.');
                }

                $this->assertCompanySlugAvailable($connection, $slug, $companyId);

                $statement = $connection->prepare(
                    'UPDATE companies
                     SET name = :name,
                         slug = :slug,
                         logo_path = COALESCE(:logo_path, logo_path),
                         updated_at = NOW(6)
                     WHERE id = :company_id'
                );
                $statement->execute([
                    'name' => $name,
                    'slug' => $slug,
                    'logo_path' => $logoPath,
                    'company_id' => $companyId,
                ]);

                $this->auditCompany($connection, 'UPDATE', $companyId, [
                    'company_id' => $companyId,
                    'old_name' => $company['name'],
                    'old_slug' => $company['slug'],
                    'new_name' => $name,
                    'new_slug' => $slug,
                    'new_logo_path' => $logoPath ?? $company['logo_path'],
                ]);

                $connection->commit();
            } catch (Throwable $throwable) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }

                throw $throwable;
            }
        }, 'Empresa atualizada com sucesso.');
    }

    public function deactivateCompany(): void
    {
        $this->executeCompanyAction(function (\PDO $connection, array $payload): void {
            $companyId = (int) ($payload['company_id'] ?? 0);
            if ($companyId <= 0) {
                throw new DomainException('Empresa inválida.');
            }

            $connection->beginTransaction();

            try {
                $company = $this->companyForUpdate($connection, $companyId);
                if ($company === null || $company['deleted_at'] !== null) {
                    throw new DomainException('Empresa indisponível para inativação.');
                }

                if ($this->isCurrentUsersLastActiveCompany($connection, $companyId)) {
                    throw new DomainException(
                        'Você não pode inativar sua última empresa ativa.'
                    );
                }

                $linkedUserIds = $this->linkedUserIdsForCompany($connection, $companyId);
                $statement = $connection->prepare(
                    'UPDATE companies
                     SET deleted_at = UTC_TIMESTAMP(6), updated_at = NOW(6)
                     WHERE id = :company_id AND deleted_at IS NULL'
                );
                $statement->execute(['company_id' => $companyId]);

                $deleteMemberships = $connection->prepare(
                    'DELETE FROM company_user WHERE company_id = :company_id'
                );
                $deleteMemberships->execute(['company_id' => $companyId]);

                $revokedUserIds = $this->revokeUsersWithoutActiveCompanies(
                    $connection,
                    $linkedUserIds
                );

                $this->auditCompany($connection, 'DELETE', $companyId, [
                    'company_id' => $companyId,
                    'company_name' => $company['name'],
                    'company_slug' => $company['slug'],
                    'revoked_company_memberships' => count($linkedUserIds),
                    'revoked_user_ids' => $revokedUserIds,
                    'message' => 'Empresa inativada e vínculos do tenant revogados.',
                ]);

                $connection->commit();
            } catch (Throwable $throwable) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }

                throw $throwable;
            }
        }, 'Empresa inativada e acessos do tenant revogados.');
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

    private function companyDirectory(\PDO $connection, array $filters): array
    {
        $where = [];
        $parameters = [];

        if ($filters['query'] !== '') {
            $where[] = '(c.name LIKE :company_name_query OR c.slug LIKE :company_slug_query)';
            $parameters['company_name_query'] = '%' . $filters['query'] . '%';
            $parameters['company_slug_query'] = '%' . $filters['query'] . '%';
        }

        if ($filters['status'] === 'active') {
            $where[] = 'c.deleted_at IS NULL';
        } elseif ($filters['status'] === 'inactive') {
            $where[] = 'c.deleted_at IS NOT NULL';
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $limit = $filters['is_filtered'] ? 50 : 12;
        $statement = $connection->prepare(
            'SELECT
                c.id,
                c.name,
                c.slug,
                c.logo_path,
                c.deleted_at,
                COUNT(DISTINCT cu.user_id) AS users_count,
                SUM(CASE WHEN u.deleted_at IS NULL THEN 1 ELSE 0 END) AS active_users_count,
                SUM(CASE WHEN u.deleted_at IS NULL AND u.password_reset_required = 1 THEN 1 ELSE 0 END) AS password_reset_users_count,
                GROUP_CONCAT(DISTINCT r.slug ORDER BY r.slug SEPARATOR \', \') AS role_slugs
             FROM companies c
             LEFT JOIN company_user cu ON cu.company_id = c.id
             LEFT JOIN usuario u ON u.id_usuario = cu.user_id
             LEFT JOIN roles r ON r.id = cu.role_id
             ' . $whereSql . '
             GROUP BY c.id, c.name, c.slug, c.logo_path, c.deleted_at
             ORDER BY c.name ASC, c.id ASC
             LIMIT ' . $limit
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue(':' . $key, $value, \PDO::PARAM_STR);
        }
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function companiesCount(\PDO $connection): int
    {
        return (int) $connection
            ->query('SELECT COUNT(*) FROM companies')
            ->fetchColumn();
    }

    private function assignableRoles(\PDO $connection): array
    {
        $roles = IdentityContext::current()->roleSlugs();
        $canAssignPrivileged = in_array('super-admin', $roles, true);
        $where = $canAssignPrivileged
            ? 'WHERE is_active = 1'
            : "WHERE is_active = 1 AND slug NOT IN ('master', 'super-admin')";

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
                GROUP_CONCAT(DISTINCT r.slug ORDER BY r.slug SEPARATOR \', \') AS role_slug
             FROM usuario u
             LEFT JOIN company_user cu ON cu.user_id = u.id_usuario
             LEFT JOIN companies c ON c.id = cu.company_id
             LEFT JOIN roles r ON r.id = cu.role_id
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

    private function companyFilters(): array
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $status = (string) ($_GET['status'] ?? 'active');
        $status = in_array($status, ['active', 'inactive', 'all'], true)
            ? $status
            : 'active';

        return [
            'query' => substr($query, 0, 120),
            'status' => $status,
            'is_filtered' => $query !== ''
                || $status !== 'active',
        ];
    }

    private function executeCompanyAction(callable $action, string $successMessage): void
    {
        $this->startSession();

        if (!$this->hasValidAdminCsrf()) {
            $_SESSION['admin_error'] = 'A sessão expirou. Tente novamente.';
            $this->redirectCompanies();
        }

        $connection = (new Database())->connect();

        try {
            $this->assertMaster();
            $action($connection, $this->payload());
            $_SESSION['admin_success'] = $successMessage;
        } catch (DomainException $exception) {
            $_SESSION['admin_error'] = $exception->getMessage();
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            $_SESSION['admin_error'] = 'Não foi possível concluir a alteração da empresa.';
        }

        $this->redirectCompanies();
    }

    private function companyForUpdate(\PDO $connection, int $companyId): ?array
    {
        $statement = $connection->prepare(
            'SELECT id, name, slug, logo_path, deleted_at
             FROM companies
             WHERE id = :company_id
             FOR UPDATE'
        );
        $statement->execute(['company_id' => $companyId]);
        $company = $statement->fetch(\PDO::FETCH_ASSOC);

        return $company === false ? null : $company;
    }

    private function assertCompanySlugAvailable(
        \PDO $connection,
        string $slug,
        int $exceptCompanyId
    ): void {
        $statement = $connection->prepare(
            'SELECT 1 FROM companies
             WHERE slug = :slug AND id <> :company_id
             LIMIT 1'
        );
        $statement->execute([
            'slug' => $slug,
            'company_id' => $exceptCompanyId,
        ]);

        if ($statement->fetchColumn() !== false) {
            throw new DomainException('Já existe uma empresa com esse slug.');
        }
    }

    private function linkedUserIdsForCompany(\PDO $connection, int $companyId): array
    {
        $statement = $connection->prepare(
            'SELECT user_id FROM company_user WHERE company_id = :company_id'
        );
        $statement->execute(['company_id' => $companyId]);

        return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    private function revokeUsersWithoutActiveCompanies(
        \PDO $connection,
        array $userIds
    ): array {
        $revoked = [];
        $membershipCheck = $connection->prepare(
            'SELECT COUNT(*)
             FROM company_user cu
             INNER JOIN companies c ON c.id = cu.company_id AND c.deleted_at IS NULL
             WHERE cu.user_id = :user_id'
        );
        $revoke = $connection->prepare(
            'UPDATE usuario
             SET deleted_at = UTC_TIMESTAMP(6)
             WHERE id_usuario = :user_id AND deleted_at IS NULL'
        );

        foreach (array_values(array_unique($userIds)) as $userId) {
            if ($userId === IdentityContext::current()->userId()) {
                continue;
            }

            $membershipCheck->execute(['user_id' => $userId]);
            if ((int) $membershipCheck->fetchColumn() > 0) {
                continue;
            }

            $revoke->execute(['user_id' => $userId]);
            if ($revoke->rowCount() > 0) {
                $revoked[] = $userId;
            }
        }

        return $revoked;
    }

    private function isCurrentUsersLastActiveCompany(
        \PDO $connection,
        int $companyId
    ): bool {
        $statement = $connection->prepare(
            'SELECT COUNT(*)
             FROM company_user cu
             INNER JOIN companies c ON c.id = cu.company_id AND c.deleted_at IS NULL
             WHERE cu.user_id = :user_id'
        );
        $statement->execute(['user_id' => IdentityContext::current()->userId()]);
        $activeMemberships = (int) $statement->fetchColumn();

        $linkedToTarget = $connection->prepare(
            'SELECT 1 FROM company_user
             WHERE user_id = :user_id AND company_id = :company_id
             LIMIT 1'
        );
        $linkedToTarget->execute([
            'user_id' => IdentityContext::current()->userId(),
            'company_id' => $companyId,
        ]);

        return $activeMemberships <= 1 && $linkedToTarget->fetchColumn() !== false;
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
            $this->assertMaster();
            $action();
            $_SESSION['admin_success'] = $successMessage;
        } catch (DomainException $exception) {
            $_SESSION['admin_error'] = $exception->getMessage();
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            $_SESSION['admin_error'] = 'Não foi possível concluir o provisionamento.';
        }

        $this->redirect();
    }

    private function jsonResponse(callable $action): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $this->assertMaster();
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

    private function redirectCompanies(): never
    {
        header('Location: /admin/companies');
        exit;
    }

    private function assertMaster(): void
    {
        $roles = IdentityContext::current()->roleSlugs();
        if (!in_array('master', $roles, true)
            && !in_array('super-admin', $roles, true)) {
            throw new ForbiddenException(
                'admin.provision',
                'Apenas usuários MASTER podem provisionar acessos.'
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

    private function storeCompanyLogo(?array $file): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK
            || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))
            || (int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new DomainException('Logo inválida ou maior que 2 MB.');
        }

        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

        if (!isset($allowedMimeTypes[$mimeType])) {
            throw new DomainException('Formato inválido. Envie logo em JPG, PNG ou WEBP.');
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/companies';
        if ((!is_dir($uploadDir) && !mkdir($uploadDir, 0750, true) && !is_dir($uploadDir))
            || !is_writable($uploadDir)) {
            throw new DomainException('Não foi possível preparar o diretório de logos.');
        }

        $fileName = sprintf(
            'company_%s.%s',
            bin2hex(random_bytes(16)),
            $allowedMimeTypes[$mimeType]
        );
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        if (!@move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new DomainException('Não foi possível salvar a logo.');
        }

        return 'companies/' . $fileName;
    }
}
