<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Context\IdentityContext;
use App\Authorization\Services\CompanyAccessGuard;
use App\Companies\Application\RegisterCompany;
use App\Companies\Application\ManageCompany;
use App\Companies\Infrastructure\PdoCompanyRepository;
use App\Exceptions\ForbiddenException;
use App\Repositories\AuditLogRepository;
use App\Services\AuditService;
use Config\Database;
use Core\Controller;
use DomainException;
use Throwable;

final class AdminCompanyController extends Controller
{
    public function createCompany(): void
    {
        $this->respond(function (): array {
            $this->assertValidFormRequest();
            $payload = $this->payload();
            $connection = (new Database())->connect();
            $identity = IdentityContext::current();
            $service = new RegisterCompany(
                $connection,
                new PdoCompanyRepository($connection),
                $identity,
                new AuditService(new AuditLogRepository($connection), $identity)
            );

            $name = (string) ($payload['name'] ?? '');
            $slug = (string) ($payload['slug'] ?? '');
            if (trim($name) === ''
                || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', strtolower(trim($slug))) !== 1) {
                throw new DomainException('Nome e slug válido são obrigatórios.');
            }

            $logoPath = $this->storeCompanyLogo($_FILES['logo'] ?? null);

            $companyId = $service->execute(
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
        $this->assertGovernanceAdmin();

        $connection = (new Database())->connect();
        $filters = $this->companyFilters();
        $companies = new PdoCompanyRepository($connection);
        $identity = IdentityContext::current();
        $guard = new CompanyAccessGuard($connection, $identity);
        $scopeUserId = $guard->isSuperAdmin() ? null : $identity->userId();

        $this->setView('Admin/companies', [
            'title' => 'Empresas - Governança SaaS',
            'companies' => $companies->directory($filters, $scopeUserId),
            'companyFilters' => $filters,
            'companiesCount' => $companies->countAll($scopeUserId),
            'activeUsersCount' => $this->activeUsersCount($connection, $scopeUserId),
            'passwordResetUsersCount' => $this->passwordResetUsersCount($connection, $scopeUserId),
            'canCreateCompany' => $this->canCreateCompany(),
            'csrfToken' => $this->csrfToken(),
            'success' => $_SESSION['admin_success'] ?? null,
            'error' => $_SESSION['admin_error'] ?? null,
        ]);

        unset($_SESSION['admin_success'], $_SESSION['admin_error']);
    }

    public function updateCompany(): void
    {
        $this->executeCompanyAction(function (\PDO $connection, array $payload): void {
            $identity = IdentityContext::current();
            $companyId = (int) ($payload['company_id'] ?? 0);
            (new CompanyAccessGuard($connection, $identity))->assertCanManage($companyId);
            (new ManageCompany(
                $connection,
                new PdoCompanyRepository($connection),
                $identity,
                new AuditService(new AuditLogRepository($connection), $identity)
            ))->update(
                $companyId,
                (string) ($payload['name'] ?? ''),
                (string) ($payload['slug'] ?? ''),
                $this->storeCompanyLogo($_FILES['logo'] ?? null)
            );
        }, 'Empresa atualizada com sucesso.');
    }

    public function deactivateCompany(): void
    {
        $this->executeCompanyAction(function (\PDO $connection, array $payload): void {
            $identity = IdentityContext::current();
            (new ManageCompany(
                $connection,
                new PdoCompanyRepository($connection),
                $identity,
                new AuditService(new AuditLogRepository($connection), $identity)
            ))->deactivate((int) ($payload['company_id'] ?? 0));
        }, 'Empresa inativada e acessos do tenant revogados.');
    }



    private function activeUsersCount(\PDO $connection, ?int $scopeUserId): int
    {
        $sql = 'SELECT COUNT(DISTINCT u.id_usuario) FROM usuario u';
        $parameters = [];
        if ($scopeUserId !== null) {
            $sql .= ' INNER JOIN company_user target_cu ON target_cu.user_id = u.id_usuario
                      INNER JOIN company_user actor_cu ON actor_cu.company_id = target_cu.company_id
                     WHERE actor_cu.user_id = :actor_user_id AND u.deleted_at IS NULL';
            $parameters['actor_user_id'] = $scopeUserId;
        } else {
            $sql .= ' WHERE u.deleted_at IS NULL';
        }
        $statement = $connection->prepare($sql);
        $statement->execute($parameters);

        return (int) $statement->fetchColumn();
    }

    private function passwordResetUsersCount(\PDO $connection, ?int $scopeUserId): int
    {
        $sql = 'SELECT COUNT(DISTINCT u.id_usuario) FROM usuario u';
        $parameters = [];
        if ($scopeUserId !== null) {
            $sql .= ' INNER JOIN company_user target_cu ON target_cu.user_id = u.id_usuario
                      INNER JOIN company_user actor_cu ON actor_cu.company_id = target_cu.company_id
                     WHERE actor_cu.user_id = :actor_user_id
                       AND u.deleted_at IS NULL AND u.password_reset_required = 1';
            $parameters['actor_user_id'] = $scopeUserId;
        } else {
            $sql .= ' WHERE u.deleted_at IS NULL AND u.password_reset_required = 1';
        }
        $statement = $connection->prepare($sql);
        $statement->execute($parameters);

        return (int) $statement->fetchColumn();
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
            $this->assertGovernanceAdmin();
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

        $this->redirectCompanies();
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

    private function redirectCompanies(): never
    {
        header('Location: /admin/companies');
        exit;
    }

    private function assertGovernanceAdmin(): void
    {
        $roles = IdentityContext::current()->roleSlugs();
        if (!array_intersect(['admin', 'super-admin'], $roles)) {
            throw new ForbiddenException(
                'admin.provision',
                'Apenas ADMIN ou SUPER-ADMIN pode gerenciar empresas.'
            );
        }
    }

    private function canCreateCompany(): bool
    {
        return array_intersect(
            ['super-admin'],
            IdentityContext::current()->roleSlugs()
        ) !== [];
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
