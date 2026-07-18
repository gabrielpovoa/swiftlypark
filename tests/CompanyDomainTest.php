<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Companies\Domain\Company;

$company = Company::register(' Jardim Europa ', 'JARDIM-EUROPA', 'companies/logo.png');
if ($company->name() !== 'Jardim Europa' || $company->slug() !== 'jardim-europa') {
    throw new RuntimeException('Company não normalizou nome e slug.');
}
if (!$company->isActive() || $company->logoPath() !== 'companies/logo.png') {
    throw new RuntimeException('Company foi criada em estado inválido.');
}

foreach ([['', 'slug'], ['Empresa', 'Slug Inválido'], ['Empresa', '../empresa']] as [$name, $slug]) {
    try {
        Company::register($name, $slug);
        throw new RuntimeException('Company aceitou dados inválidos.');
    } catch (DomainException) {
    }
}

echo "Company domain test passed\n";
