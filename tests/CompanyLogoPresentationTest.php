<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$expectations = [
    'app/Views/partials/header.php' => 'h-12 w-12',
    'app/Views/Admin/companies.php' => 'h-16 w-16',
    'app/Views/Admin/company-pricing.php' => 'h-16 w-16',
    'app/Views/Finance/index.php' => 'h-20 w-20',
    'app/Views/Finance/print.php' => 'h-20 w-20',
    'app/Views/Logs/print.php' => 'h-20 w-20',
];

foreach ($expectations as $relativePath => $sizeMarker) {
    $contents = file_get_contents($root . '/' . $relativePath);
    if ($contents === false) {
        throw new RuntimeException('Não foi possível carregar ' . $relativePath);
    }

    if (!str_contains($contents, $sizeMarker)
        || !str_contains($contents, 'object-contain')) {
        throw new RuntimeException(
            sprintf('Logo não foi ampliada proporcionalmente em %s.', $relativePath)
        );
    }
}

$companyView = file_get_contents($root . '/app/Views/Admin/company-pricing.php');
if ($companyView === false
    || !str_contains($companyView, 'alt="Logo <?= $escape($company[\'name\']) ?>"')) {
    throw new RuntimeException('Logo da empresa não possui descrição acessível.');
}

echo "Company logo presentation test passed\n";
