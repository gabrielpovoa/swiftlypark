<?php
use Core\Router;
use Core\Controller;
use App\Controllers\HomeController;
use App\Controllers\LoginController;
use App\Controllers\PasswordRecController;
use App\Parking\Presentation\VacancyController;
use App\Parking\Presentation\CreateVacancyController;
use App\Controllers\LogsController;
use App\Controllers\ContactController;
use App\Controllers\AboutController;
use App\Controllers\ProfileController;
use App\Controllers\AuditController;
use App\Identity\Presentation\IdentityManagementController;
use App\Finance\Presentation\FinanceController;
use App\Identity\Presentation\AdminUserProvisioningController;
use App\Billing\Presentation\AdminMonthlyContractController;
use App\Billing\Presentation\AdminCompanyBillingController;
use App\Controllers\AdminCompanyController;
use App\Controllers\ApiTenantController;
use App\Controllers\DashboardGlobalController;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\AccessRevokedException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\SecurityCriticalException;
use App\Context\IdentityContext;
use App\Context\TenantContext;
use App\Middleware\IdentityMiddleware;
use App\Middleware\AuthorizeMiddleware;
use App\Middleware\TenantMiddleware;
use App\Middleware\RegistrationBlockedMiddleware;
use App\Authorization\Repositories\RbacRepository;
use App\Authorization\Services\RolePermissionResolver;
use App\Repositories\AuditLogRepository;
use App\Services\AuthorizationService;
use App\Services\SecurityAuditService;
use Config\Database;

$router = new Router();

// Função para proteger rotas privadas
function authRequired($callback)
{
    return function (...$arguments) use ($callback) {
        try {
            (new IdentityMiddleware())->handle(function () use ($callback, $arguments) {
                (new TenantMiddleware())->handle(
                    static fn () => $callback(...$arguments)
                );
            });
        } catch (AccessRevokedException $exception) {
            header('Location: /login?revoked=1');
            exit;
        } catch (UnauthorizedException $exception) {
            header('Location: /login');
            exit;
        } catch (ForbiddenException|SecurityCriticalException $exception) {
            if ($exception instanceof SecurityCriticalException) {
                auditSecurityCriticalException($exception);
            }

            (new Controller())->render403();
        }
    };
}

function authIdentityRequired($callback)
{
    return function (...$arguments) use ($callback) {
        try {
            (new IdentityMiddleware())->handle(
                static fn () => $callback(...$arguments)
            );
        } catch (AccessRevokedException $exception) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(403);
            echo json_encode(['error' => $exception->getMessage()]);
            exit;
        } catch (UnauthorizedException $exception) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(401);
            echo json_encode(['error' => 'A autenticação é obrigatória.']);
            exit;
        }
    };
}

function auditSecurityCriticalException(SecurityCriticalException $exception): void
{
    try {
        $identity = IdentityContext::current();
        $connection = (new Database())->connect();
        (new SecurityAuditService(
            new AuditLogRepository($connection),
            $identity
        ))->recordCriticalQueryBlocked(
            trim((string) ($_GET['url'] ?? $_SERVER['REQUEST_URI'] ?? ''), '/'),
            $exception->securityCode(),
            $exception->getMessage()
        );
    } catch (\Throwable) {
    }
}

function permissionRequired(string $permission, string $route, $callback)
{
    return authRequired(function (...$arguments) use ($permission, $route, $callback) {
        $identity = IdentityContext::current();
        $connection = (new Database())->connect();
        $middleware = new AuthorizeMiddleware(
            new AuthorizationService($identity),
            new SecurityAuditService(
                new AuditLogRepository($connection),
                $identity
            )
        );

        try {
            $middleware->handle(
                $permission,
                $route,
                static fn () => $callback(...$arguments)
            );
        } catch (ForbiddenException $exception) {
            (new Controller())->render403();
        } catch (SecurityCriticalException $exception) {
            auditSecurityCriticalException($exception);
            (new Controller())->render403();
        }
    });
}

function hasGlobalPlatformRole(int $userId): bool
{
    $authorization = (new RolePermissionResolver(
        new RbacRepository((new Database())->connect())
    ))->resolve($userId, null);
    $roles = $authorization->roleSlugs();

    return in_array('super-admin', $roles, true)
        || in_array('master', $roles, true);
}

function superAdminRequired($callback)
{
    return function () use ($callback) {
        try {
            (new IdentityMiddleware())->handle(function () use ($callback) {
                $identity = IdentityContext::current();

                if (!hasGlobalPlatformRole($identity->userId())) {
                    (new Controller())->render403();
                }

                $callback();
            });
        } catch (AccessRevokedException $exception) {
            header('Location: /login?revoked=1');
            exit;
        } catch (UnauthorizedException $exception) {
            header('Location: /login');
            exit;
        } catch (ForbiddenException|SecurityCriticalException $exception) {
            if ($exception instanceof SecurityCriticalException) {
                auditSecurityCriticalException($exception);
            }

            (new Controller())->render403();
        }
    };
}

// Rota raiz: login ou home conforme sessão
$router->get('', function () {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        $controller = new LoginController();
    } else {
        try {
            (new IdentityMiddleware())->handle(function () {
                $identity = IdentityContext::current();
                $isPlatformAdmin = hasGlobalPlatformRole($identity->userId());

                header('Location: ' . ($isPlatformAdmin ? '/admin/dashboard' : '/operational/dashboard'));
                exit;
            });
        } catch (AccessRevokedException $exception) {
            header('Location: /login?revoked=1');
            exit;
        } catch (UnauthorizedException $exception) {
            header('Location: /login');
            exit;
        }

        return;
    }
    $controller->index();
});

$router->get('operational/dashboard', permissionRequired('dashboard.view', 'operational/dashboard', function () {
    (new HomeController())->index();
}));

// Login
$router->get('login', function () {
    $controller = new LoginController();
    $controller->index();
});
$router->post('login/authenticate', function () {
    $controller = new LoginController();
    $controller->authenticate();
});
$router->get('login/password-required', function () {
    $controller = new LoginController();
    $controller->showPasswordRequired();
});
$router->post('login/password-required', function () {
    $controller = new LoginController();
    $controller->updateRequiredPassword();
});
$router->get('login/logout', function () {
    $controller = new LoginController();
    $controller->logout();
});
$router->get('login/recovery', function () {
    $controller = new PasswordRecController();
    $controller->showForm();
});
$router->post('login/recovery/send', function () {
    $controller = new PasswordRecController();
    $controller->requestOtp();
});
$router->post('login/recovery/verify', function () {
    $controller = new PasswordRecController();
    $controller->verifyOtp();
});
$router->get('login/recovery/reset', function () {
    $controller = new PasswordRecController();
    $controller->showResetForm();
});
$router->post('login/recovery/reset', function () {
    $controller = new PasswordRecController();
    $controller->resetPassword();
});

$router->get('CreateAcc', function () {
    (new RegistrationBlockedMiddleware())->handle();
});
$router->post('CreateAcc/create', function () {
    (new RegistrationBlockedMiddleware())->handle();
});
$router->get('CreateAcc/create', function () {
    (new RegistrationBlockedMiddleware())->handle();
});

// Perfil (somente logado)
$router->get('Profile', authRequired(function () {
    $controller = new ProfileController();
    $controller->index();
}));
$router->post('Profile/updateProfile', authRequired(function () {
    $controller = new ProfileController();
    $controller->updateProfile();
}));
$router->get('Profile/changePassword', permissionRequired('profile.password.update', 'Profile/changePassword', function () {
    $controller = new ProfileController();
    $controller->changePassword();
}));
$router->post('Profile/changePassword', permissionRequired('profile.password.update', 'Profile/changePassword', function () {
    $controller = new ProfileController();
    $controller->changePassword();
}));
$router->post('Profile/uploadPhoto', permissionRequired('profile.photo.update', 'Profile/uploadPhoto', function () {
    $controller = new ProfileController();
    $controller->uploadPhoto();
}));

// Vagas
$router->get('vacancy', permissionRequired('vehicle.view', 'vacancy', function () {
    $controller = new VacancyController();
    $controller->index();
}));
$router->get('vacancy/apply', permissionRequired('vehicle.checkin', 'vacancy/apply', function () {
    $controller = new VacancyController();
    $controller->apply();
}));
$router->post('vacancy/apply', permissionRequired('vehicle.checkin', 'vacancy/apply', function () {
    $controller = new VacancyController();
    $controller->apply();
}));
$router->get('vacancy/manage', permissionRequired('vehicle.view', 'vacancy/manage', function () {
    $controller = new VacancyController();
    $controller->manage();
}));
$router->post('vacancy/finish', permissionRequired('vehicle.checkout', 'vacancy/finish', function () {
    $controller = new VacancyController();
    $controller->finishVacancy();
}));
// CreateVacancy
$router->get('CreateVacancy', permissionRequired('vacancy.create', 'CreateVacancy', function () {
    $controller = new CreateVacancyController();
    $controller->index();
}));
$router->post('CreateVacancy/store', permissionRequired('vacancy.create', 'CreateVacancy/store', function () {
    $controller = new CreateVacancyController();
    $controller->store();
}));


$router->get('logs/options', permissionRequired('report.view', 'logs/options', function () {
    $controller = new LogsController();
    $controller->options();
}));

$router->get('logs/print', permissionRequired('report.view', 'logs/print', function () {
    $controller = new LogsController();
    $controller->print();
}));

$router->get('audit', authRequired(function () {
    (new AuditController())->index();
}));

$router->get('identity', permissionRequired('identity.view', 'identity', function () {
    (new IdentityManagementController())->index();
}));
$router->post('identity/revoke', permissionRequired('identity.manage', 'identity/revoke', function () {
    (new IdentityManagementController())->revoke();
}));
$router->post('identity/reactivate', permissionRequired('identity.manage', 'identity/reactivate', function () {
    (new IdentityManagementController())->reactivate();
}));
$router->post('identity/permissions', permissionRequired('identity.manage', 'identity/permissions', function () {
    (new IdentityManagementController())->permissions();
}));

$router->get('admin', permissionRequired('identity.manage', 'admin', function () {
    (new AdminUserProvisioningController())->index();
}));
$router->get('admin/dashboard', superAdminRequired(function () {
    (new DashboardGlobalController())->index();
}));
$router->get('admin/companies', permissionRequired('identity.manage', 'admin/companies', function () {
    (new AdminCompanyController())->companiesIndex();
}));
$router->get('admin/companies/pricing', permissionRequired('identity.manage', 'admin/companies/pricing', function () {
    (new AdminCompanyBillingController())->show();
}));
$router->get('admin/companies/company_id={company_id}', permissionRequired('identity.manage', 'admin/companies/company', function (int $companyId) {
    (new AdminCompanyBillingController())->show($companyId);
}));
$router->post('admin/companies/create', authRequired(function () {
    (new AdminCompanyController())->createCompany();
}));
$router->post('admin/companies/update', authRequired(function () {
    (new AdminCompanyController())->updateCompany();
}));
$router->post('admin/companies/pricing', authRequired(function () {
    (new AdminCompanyBillingController())->update();
}));
$router->post('admin/companies/company_id={company_id}', authRequired(function (int $companyId) {
    (new AdminCompanyBillingController())->update($companyId);
}));
$router->post('admin/companies/company_id={company_id}/contracts/create', authRequired(function (int $companyId) {
    (new AdminMonthlyContractController())->create($companyId);
}));
$router->post('admin/companies/company_id={company_id}/contracts/renew', authRequired(function (int $companyId) {
    (new AdminMonthlyContractController())->renew($companyId);
}));
$router->post('admin/companies/company_id={company_id}/contracts/cancel', authRequired(function (int $companyId) {
    (new AdminMonthlyContractController())->cancel($companyId);
}));
$router->post('admin/companies/deactivate', authRequired(function () {
    (new AdminCompanyController())->deactivateCompany();
}));
$router->post('admin/users/create', authRequired(function () {
    (new AdminUserProvisioningController())->createUser();
}));
$router->post('admin/users/link-company', authRequired(function () {
    (new AdminUserProvisioningController())->linkExistingUser();
}));
$router->post('admin/users/send-temporary-password', authRequired(function () {
    (new AdminUserProvisioningController())->sendTemporaryPassword();
}));
$router->post('admin/users/remove-company', authRequired(function () {
    (new AdminUserProvisioningController())->removeCompanyAccess();
}));
$router->post('admin/users/company-permissions', authRequired(function () {
    (new AdminUserProvisioningController())->syncCompanyPermissions();
}));

$router->get('finance', permissionRequired('finance.view', 'finance', function () {
    (new FinanceController())->index();
}));
$router->get('finance/data', permissionRequired('finance.view', 'finance/data', function () {
    (new FinanceController())->data();
}));
$router->get('finance/export', permissionRequired('finance.view', 'finance/export', function () {
    (new FinanceController())->exportCsv();
}));
$router->get('finance/print', permissionRequired('finance.view', 'finance/print', function () {
    (new FinanceController())->printPdf();
}));
$router->post('finance/refund', permissionRequired('finance.adjust', 'finance/refund', function () {
    (new FinanceController())->refund();
}));

$router->get('api/v1/user/tenants', authIdentityRequired(function () {
    (new ApiTenantController())->tenants();
}));
$router->post('api/v1/tenant/switch', authIdentityRequired(function () {
    (new ApiTenantController())->switchTenant();
}));
$router->post('api/v1/support/profile', authIdentityRequired(function () {
    (new ApiTenantController())->supportProfile();
}));
$router->get('api/v1/permissions/context', authIdentityRequired(function () {
    (new ApiTenantController())->permissionsContext();
}));


// Contato
$router->get('Contact', authRequired(function () {
    $controller = new ContactController();
    $controller->index();
}));
$router->post('Contact/SendSMTP', authRequired(function () {
    $controller = new ContactController();
    $controller->SendSMTP();
}));

// Sobre
$router->get('About', authRequired(function () {
    $controller = new AboutController();
    $controller->index();
}));

return $router;
