<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$migration = file_get_contents(
    $root . '/database/migrations/20260802_create_company_vehicle_types.sql'
);
$repository = file_get_contents(
    $root . '/app/Companies/Infrastructure/PdoCompanyVehicleTypeRepository.php'
);
$billing = file_get_contents(
    $root . '/app/Billing/Presentation/AdminCompanyBillingController.php'
);
$parking = file_get_contents(
    $root . '/app/Parking/Infrastructure/PdoParkingGateway.php'
);
$companyView = file_get_contents($root . '/app/Views/Admin/company-pricing.blade.php');
$vacancyView = file_get_contents($root . '/app/Views/Vacancy/vacancy.blade.php');
$dashboard = file_get_contents($root . '/app/Views/HomeDash/homedash.php');
$contracts = file_get_contents(
    $root . '/app/Billing/Presentation/AdminMonthlyContractController.php'
);

if (in_array(false, [
    $migration,
    $repository,
    $billing,
    $parking,
    $companyView,
    $vacancyView,
    $dashboard,
    $contracts,
], true)) {
    throw new RuntimeException('Não foi possível carregar a capacidade de tipos de vaga.');
}

foreach ([
    'CREATE TABLE company_vehicle_types',
    "ENUM('carro', 'moto', 'caminhao', 'app')",
    'PRIMARY KEY (company_id, vehicle_type)',
    'FOREIGN KEY (company_id) REFERENCES companies (id)',
] as $marker) {
    if (!str_contains($migration, $marker)) {
        throw new RuntimeException('Migration incompleta: ' . $marker);
    }
}

foreach ([
    'public function enabledTypes(',
    'public function isEnabled(',
    'public function sync(',
    'Mantenha ao menos um tipo de vaga habilitado.',
    'data-vehicle-type-toggle',
    'data-tariff-card',
] as $marker) {
    if (!str_contains($repository . $billing . $companyView, $marker)) {
        throw new RuntimeException('Gestão de tipos incompleta: ' . $marker);
    }
}

foreach ([
    'Este tipo de vaga não é oferecido pela empresa.',
    'isVehicleTypeEnabled($tipoVeiculo)',
    "in_array(\$item['type'], \$enabledVehicleTypes, true)",
    "in_array(\$action[0], \$enabledVehicleTypes, true)",
    'Este tipo de veículo está desabilitado para a empresa.',
] as $marker) {
    if (!str_contains($parking . $vacancyView . $dashboard . $contracts, $marker)) {
        throw new RuntimeException('Bloqueio operacional incompleto: ' . $marker);
    }
}

echo "Company vehicle type toggle test passed\n";
