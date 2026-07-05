<?php
use Core\Router;
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

$router = new Router();

// Função para proteger rotas privadas
function authRequired($callback)
{
    return function () use ($callback) {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $callback();
    };
}

// Rota raiz: login ou home conforme sessão
$router->get('', function () {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        $controller = new LoginController();
    } else {
        $controller = new HomeController();
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
$router->get('Profile/changePassword', authRequired(function () {
    $controller = new ProfileController();
    $controller->changePassword();
}));
$router->post('Profile/changePassword', authRequired(function () {
    $controller = new ProfileController();
    $controller->changePassword();
}));
$router->post('Profile/uploadPhoto', authRequired(function () {
    $controller = new ProfileController();
    $controller->uploadPhoto();
}));

// Vagas
$router->get('vacancy', authRequired(function () {
    $controller = new VacancyController();
    $controller->index();
}));
$router->get('vacancy/apply', authRequired(function () {
    $controller = new VacancyController();
    $controller->apply();
}));
$router->post('vacancy/apply', authRequired(function () {
    $controller = new VacancyController();
    $controller->apply();
}));
$router->get('vacancy/manage', authRequired(function () {
    $controller = new VacancyController();
    $controller->manage();
}));
$router->post('vacancy/finish', authRequired(function () {
    $controller = new VacancyController();
    $controller->finishVacancy();
}));
// CreateVacancy
$router->get('CreateVacancy', authRequired(function () {
    $controller = new CreateVacancy();
    $controller->index();
}));
$router->post('CreateVacancy/store', authRequired(function () {
    $controller = new CreateVacancy();
    $controller->store();
}));


$router->get('logs/options', authRequired(function () {
    $controller = new LogsController();
    $controller->options();
}));

$router->get('logs/print', authRequired(function () {
    $controller = new LogsController();
    $controller->print();
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
