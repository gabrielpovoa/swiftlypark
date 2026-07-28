<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Core\View\BladeViewRenderer;

$cache = sys_get_temp_dir() . '/swiftlypark-heavy-blade-test-' . bin2hex(random_bytes(6));
$renderer = new BladeViewRenderer(__DIR__ . '/../app/Views', $cache);

$company = [
    'id' => 5,
    'name' => 'Empresa <Segura>',
    'trade_name' => 'Empresa <Segura>',
    'legal_name' => 'Empresa Teste Ltda.',
    'slug' => 'empresa-segura',
    'cnpj' => '',
    'logo_path' => null,
    'is_mensalista' => 0,
    'deleted_at' => null,
    'users_count' => 1,
    'active_users_count' => 1,
    'password_reset_users_count' => 0,
    'role_slugs' => 'admin',
];
$commonCompanyData = [
    'company' => $company,
    'csrfToken' => 'csrf-test',
    'success' => null,
    'error' => null,
];

$views = [
    'Admin/companies' => [
        'companies' => [$company],
        'companyFilters' => ['query' => '', 'status' => 'active', 'is_filtered' => false],
        'companiesCount' => 1,
        'activeUsersCount' => 1,
        'passwordResetUsersCount' => 0,
        'canCreateCompany' => true,
        'csrfToken' => 'csrf-test',
        'success' => null,
        'error' => null,
    ],
    'Admin/company-pricing' => $commonCompanyData + [
        'tariffs' => [],
        'enabledVehicleTypes' => ['carro', 'moto', 'caminhao', 'app'],
        'monthlyContracts' => [],
        'availableUsers' => [],
        'companyMembers' => [],
        'assignableRoles' => [],
    ],
    'Vacancy/vacancy' => [
        'counts' => ['carro' => 1, 'moto' => 2, 'caminhao' => 0, 'app' => 1],
        'enabledVehicleTypes' => ['carro', 'moto', 'app'],
        'canCheckin' => true,
        'canCreateVacancy' => true,
    ],
    'Vacancy/CreateVacancy' => [
        'title' => 'Criar vaga',
        'enabledVehicleTypes' => ['carro', 'moto'],
        'errorMessage' => null,
        'successMessage' => null,
    ],
    'Vacancy/apply' => [
        'type' => 'carro',
        'id_vaga' => 10,
        'vehicleTypes' => [
            'carro' => [
                'title' => 'Carro',
                'icon' => 'car',
                'color' => 'text-blue-400',
                'bg' => 'bg-blue-500/10',
            ],
        ],
        'vehicle' => [
            'title' => 'Carro',
            'icon' => 'car',
            'color' => 'text-blue-400',
            'bg' => 'bg-blue-500/10',
        ],
        'formData' => [],
        'alert' => null,
    ],
    'Vacancy/manage' => [
        'filtros' => [],
        'vagas' => [],
        'checkoutCsrfToken' => 'csrf-test',
    ],
    'Vacancy/noVacancy' => [
        'type' => 'caminhao',
        'message' => 'Tipo indisponível',
        'canCreateVacancy' => false,
    ],
];

set_error_handler(
    static fn (int $severity, string $message, string $file, int $line): never =>
        throw new ErrorException($message, 0, $severity, $file, $line)
);

try {
    $rendered = [];
    foreach ($views as $view => $data) {
        $output = $renderer->render($view, $data);
        if (trim($output) === '') {
            throw new RuntimeException("A view {$view} foi renderizada vazia.");
        }
        $rendered[$view] = $output;
    }

    if (str_contains($rendered['Admin/companies'], '<Segura>')
        || !str_contains($rendered['Admin/companies'], 'Empresa &lt;Segura&gt;')) {
        throw new RuntimeException('A view de empresas não escapou o nome potencialmente inseguro.');
    }
} finally {
    restore_error_handler();
    foreach (glob($cache . '/*') ?: [] as $compiled) {
        unlink($compiled);
    }
    rmdir($cache);
}

echo "Heavy Blade views test passed\n";
