<?php
use Core\Router;
use Core\Controller;
use App\Controllers\HomeController;
use App\Controllers\LoginController;
use App\Controllers\PasswordRecController;
use App\Controllers\VacancyController;
use App\Controllers\CreateVacancy;
use App\Controllers\LogsController;
use App\Controllers\ContactController;
use App\Controllers\AboutController;
use App\Controllers\ProfileController;
use App\Controllers\AuditController;
use App\Controllers\IdentityManagementController;
use App\Controllers\FinanceController;
use App\Controllers\AdminProvisioningController;
use App\Controllers\ApiTenantController;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\AccessRevokedException;
use App\Exceptions\ForbiddenException;
use App\Context\IdentityContext;
use App\Context\TenantContext;
use App\Middleware\IdentityMiddleware;
use App\Middleware\AuthorizeMiddleware;
use App\Middleware\TenantMiddleware;
use App\Middleware\RegistrationBlockedMiddleware;
use App\Repositories\AuditLogRepository;
use App\Services\AuthorizationService;
use App\Services\SecurityAuditService;
use Config\Database;

$router = new Router();

// Função para proteger rotas privadas
function authRequired($callback)
{
    return function () use ($callback) {
        try {
            (new IdentityMiddleware())->handle(function () use ($callback) {
                (new TenantMiddleware())->handle($callback);
            });
        } catch (AccessRevokedException $exception) {
            header('Location: /login?revoked=1');
            exit;
        } catch (UnauthorizedException $exception) {
            header('Location: /login');
            exit;
        }
    };
}

function authIdentityRequired($callback)
{
    return function () use ($callback) {
        try {
            (new IdentityMiddleware())->handle($callback);
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

function permissionRequired(string $permission, string $route, $callback)
{
    return authRequired(function () use ($permission, $route, $callback) {
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
            $middleware->handle($permission, $route, $callback);
        } catch (ForbiddenException $exception) {
            (new Controller())->render403();
        }
    });
}

// Rota raiz: login ou home conforme sessão
$router->get('', function () {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        $controller = new LoginController();
    } else {
        $protectedDashboard = permissionRequired(
            'dashboard.view',
            '',
            function () {
                (new HomeController())->index();
            }
        );
        $protectedDashboard();
        return;
    }
    $controller->index();
});

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
    $controller = new CreateVacancy();
    $controller->index();
}));
$router->post('CreateVacancy/store', permissionRequired('vacancy.create', 'CreateVacancy/store', function () {
    $controller = new CreateVacancy();
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

$router->get('audit', permissionRequired('audit.view', 'audit', function () {
    (new AuditController())->index();
}));

$router->get('identity', permissionRequired('identity.view', 'identity', function () {
    (new IdentityManagementController())->index();
}));
$router->post('identity/revoke', permissionRequired('identity.manage', 'identity/revoke', function () {
    (new IdentityManagementController())->revoke();
}));
$router->post('identity/permissions', permissionRequired('identity.manage', 'identity/permissions', function () {
    (new IdentityManagementController())->permissions();
}));

$router->get('admin', permissionRequired('identity.manage', 'admin', function () {
    (new AdminProvisioningController())->index();
}));
$router->get('admin/companies', permissionRequired('identity.manage', 'admin/companies', function () {
    (new AdminProvisioningController())->companiesIndex();
}));
$router->post('admin/companies/create', authRequired(function () {
    (new AdminProvisioningController())->createCompany();
}));
$router->post('admin/companies/update', authRequired(function () {
    (new AdminProvisioningController())->updateCompany();
}));
$router->post('admin/companies/deactivate', authRequired(function () {
    (new AdminProvisioningController())->deactivateCompany();
}));
$router->post('admin/users/create', authRequired(function () {
    (new AdminProvisioningController())->createUser();
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
