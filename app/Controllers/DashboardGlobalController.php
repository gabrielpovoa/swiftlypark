<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Context\IdentityContext;
use App\Authorization\Repositories\RbacRepository;
use App\Authorization\Services\RolePermissionResolver;
use App\Repositories\AuditLogRepository;
use App\Repositories\GlobalDashboardRepository;
use Config\Database;
use Core\Controller;

final class DashboardGlobalController extends Controller
{
    public function index(): void
    {
        $identity = IdentityContext::current();

        $connection = (new Database())->connect();

        if (!$this->hasGlobalPlatformRole($connection, $identity->userId())) {
            (new HomeController())->index();

            return;
        }

        $repository = new GlobalDashboardRepository($connection);
        $auditLogs = new AuditLogRepository($connection);

        $this->recordImpersonationEnd($auditLogs);
        $this->recordDashboardAccess($auditLogs);

        $this->setView('Admin/dashboard-global', [
            'title' => 'Dashboard Global - SwiftlyPark',
            'kpis' => $repository->overview(),
            'topCompanies' => $repository->topCompaniesByUsage(),
            'revenueByCompany' => $repository->revenueByCompany(),
            'securityAlerts' => $repository->recentSecurityAlerts(),
        ]);
    }

    private function recordDashboardAccess(AuditLogRepository $auditLogs): void
    {
        $identity = IdentityContext::current();

        $auditLogs->insert([
            'user_id' => $identity->userId(),
            'company_id' => null,
            'actor_email' => $identity->email(),
            'action' => 'UPDATE',
            'entity' => 'global_dashboard',
            'entity_id' => 'admin/dashboard',
            'old_values' => null,
            'new_values' => json_encode([
                'event' => 'GLOBAL_DASHBOARD_ACCESS',
                'role_slugs' => $identity->roleSlugs(),
            ], JSON_THROW_ON_ERROR),
            'ip_address' => $identity->ipAddress(),
            'request_id' => $identity->requestId(),
            'created_at' => $identity->requestedAt()->format('Y-m-d H:i:s.u'),
        ]);
    }

    private function recordImpersonationEnd(AuditLogRepository $auditLogs): void
    {
        if (empty($_SESSION['support_impersonation']['company_id'])) {
            return;
        }

        $identity = IdentityContext::current();
        $companyId = (int) $_SESSION['support_impersonation']['company_id'];
        $companyName = (string) ($_SESSION['support_impersonation']['company_name'] ?? '');

        $auditLogs->insert([
            'user_id' => $identity->userId(),
            'company_id' => $companyId,
            'actor_email' => $identity->email(),
            'action' => 'UPDATE',
            'entity' => 'support_impersonation',
            'entity_id' => (string) $companyId,
            'old_values' => null,
            'new_values' => json_encode([
                'event' => 'IMPERSONATION_ENDED',
                'super_admin_user_id' => $identity->userId(),
                'target_company_id' => $companyId,
                'target_company_name' => $companyName,
                'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ], JSON_THROW_ON_ERROR),
            'ip_address' => $identity->ipAddress(),
            'request_id' => $identity->requestId(),
            'created_at' => $identity->requestedAt()->format('Y-m-d H:i:s.u'),
        ]);

        unset($_SESSION['support_impersonation'], $_SESSION['company_id']);
    }

    private function hasGlobalPlatformRole(\PDO $connection, int $userId): bool
    {
        $authorization = (new RolePermissionResolver(
            new RbacRepository($connection)
        ))->resolve($userId, null);
        $roles = $authorization->roleSlugs();

        return in_array('super-admin', $roles, true);
    }
}
