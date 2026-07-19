<?php

declare(strict_types=1);

namespace App\Identity\Presentation;

use App\Context\IdentityContext;
use App\Identity\Infrastructure\IdentityManagementRepository;
use App\Identity\Application\IdentityManagementService;
use App\Repositories\AuditLogRepository;
use App\Transactions\TransactionManager;
use Config\Database;
use Core\Controller;
use Throwable;
use App\Services\QueuedPasswordRecoveryMailer;

final class IdentityManagementController extends Controller
{
    private const PER_PAGE = 10;

    public function index(): void
    {
        $this->startSession();
        $connection = (new Database())->connect();
        $repository = new IdentityManagementRepository($connection);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = $this->filters();
        $users = $repository->paginate($page, self::PER_PAGE, $filters);

        foreach ($users as &$user) {
            $user['role_slugs'] = $user['roles'] === null
                ? []
                : explode(',', $user['roles']);
            $user['direct_permissions'] = $repository->directPermissionIds(
                (int) $user['id_usuario']
            );
            $user['role_permissions'] = $repository->rolePermissionIds(
                (int) $user['id_usuario']
            );
        }
        unset($user);

        $this->setView('Identity/index', [
            'title' => 'Gestão de Identidade - SwiftlyPark',
            'users' => $users,
            'permissions' => $repository->permissions(),
            'companies' => $repository->companiesForFilter(),
            'filters' => $filters,
            'page' => $page,
            'totalPages' => max(
                1,
                (int) ceil($repository->countUsers($filters) / self::PER_PAGE)
            ),
            'csrfToken' => $this->csrfToken(),
            'currentUserId' => IdentityContext::current()->userId(),
            'success' => $_SESSION['identity_success'] ?? null,
            'error' => $_SESSION['identity_error'] ?? null,
        ]);

        unset($_SESSION['identity_success'], $_SESSION['identity_error']);
    }

    public function revoke(): void
    {
        $this->executeAction(function (IdentityManagementService $service): void {
            $service->revoke((int) ($_POST['user_id'] ?? 0));
            $_SESSION['identity_success'] = 'Acesso revogado com sucesso.';
        });
    }

    public function reactivate(): void
    {
        $this->executeAction(function (IdentityManagementService $service): void {
            $service->reactivate((int) ($_POST['user_id'] ?? 0));
            $_SESSION['identity_success'] = 'Usuário reativado e senha temporária enviada.';
        });
    }

    public function permissions(): void
    {
        $this->executeAction(function (IdentityManagementService $service): void {
            $permissionIds = $_POST['permissions'] ?? [];
            $service->syncPermissions(
                (int) ($_POST['user_id'] ?? 0),
                is_array($permissionIds) ? $permissionIds : []
            );
            $_SESSION['identity_success'] = 'Permissões extras atualizadas.';
        });
    }

    private function executeAction(callable $action): void
    {
        $this->startSession();

        if (!$this->hasValidCsrfToken()) {
            $_SESSION['identity_error'] = 'A sessão expirou. Tente novamente.';
            $this->redirect();
        }

        $connection = (new Database())->connect();
        $service = new IdentityManagementService(
            new IdentityManagementRepository($connection),
            new AuditLogRepository($connection),
            new TransactionManager($connection),
            IdentityContext::current(),
            null,
            null,
            QueuedPasswordRecoveryMailer::fromConnection($connection)
        );

        try {
            $action($service);
        } catch (\App\Exceptions\ForbiddenException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            $_SESSION['identity_error'] = 'Não foi possível concluir a alteração.';
        }

        $this->redirect();
    }

    private function csrfToken(): string
    {
        if (!isset($_SESSION['identity_csrf'])) {
            $_SESSION['identity_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['identity_csrf'];
    }

    private function filters(): array
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $companyId = filter_var(
            $_GET['company_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $status = (string) ($_GET['status'] ?? 'all');
        $status = in_array($status, ['all', 'active', 'revoked'], true)
            ? $status
            : 'all';

        return [
            'query' => substr($query, 0, 120),
            'company_id' => $companyId === false ? null : (int) $companyId,
            'status' => $status,
            'is_filtered' => $query !== ''
                || $companyId !== false
                || $status !== 'all',
        ];
    }

    private function hasValidCsrfToken(): bool
    {
        return isset($_SESSION['identity_csrf'], $_POST['csrf_token'])
            && hash_equals(
                $_SESSION['identity_csrf'],
                (string) $_POST['csrf_token']
            );
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function redirect(): never
    {
        header('Location: /identity');
        exit;
    }
}
