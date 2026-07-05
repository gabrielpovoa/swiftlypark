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
use App\Controllers\CreateAccController;
use App\Controllers\ProfileController;
use App\Controllers\AuditController;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ForbiddenException;
use App\Context\IdentityContext;
use App\Middleware\IdentityMiddleware;
use App\Middleware\AuthorizeMiddleware;
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
            (new IdentityMiddleware())->handle($callback);
        } catch (UnauthorizedException $exception) {
            header('Location: /login');
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

// Cadastro de usuário
$router->get('CreateAcc', function () {
    $controller = new CreateAccController();
    $controller->index();
});
$router->post('CreateAcc/create', function () {
    $controller = new CreateAccController();
    $controller->createAcc();
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
