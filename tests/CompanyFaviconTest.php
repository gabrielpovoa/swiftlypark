<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$script = file_get_contents($root . '/public/js/CompanyFavicon.js');
$head = file_get_contents($root . '/app/Views/partials/head.php');
$header = file_get_contents($root . '/app/Views/partials/header.php');
$footer = file_get_contents($root . '/app/Views/partials/footer.php');
$financePrint = file_get_contents($root . '/app/Views/Finance/print.php');
$logsPrint = file_get_contents($root . '/app/Views/Logs/print.php');
$fallback = $root . '/public/images/favicons-swiftlypark.png';

if (in_array(false, [
    $script,
    $head,
    $header,
    $footer,
    $financePrint,
    $logsPrint,
], true)) {
    throw new RuntimeException('Não foi possível carregar o fluxo de favicon.');
}

if (!is_file($fallback) || filesize($fallback) < 1) {
    throw new RuntimeException('Favicon padrão da SwiftlyPark não foi encontrado.');
}

foreach ([
    "const FALLBACK_FAVICON = '/images/favicons-swiftlypark.png'",
    'async function createCompanyFavicon(',
    "canvas.toDataURL('image/png')",
    "context.fillStyle = '#11151e'",
    'drawContainedImage(context, image)',
    'resolved.origin === window.location.origin',
    'let refreshSequence = 0',
    'applyFavicon(logoUrl)',
    'refreshId === refreshSequence',
    'refreshCompanyFavicon(currentCompanyLogo(context))',
] as $marker) {
    if (!str_contains($script, $marker)) {
        throw new RuntimeException('Transformação segura do favicon incompleta: ' . $marker);
    }
}

if (str_contains($script, "async function refreshCompanyFavicon(logoPath) {\n        applyFavicon(FALLBACK_FAVICON);")) {
    throw new RuntimeException('A atualização com logo ainda pisca para o favicon padrão.');
}

if (!str_contains($head, 'rel="icon"')
    || !str_contains($head, '/images/favicons-swiftlypark.png')) {
    throw new RuntimeException('Head não declara o favicon padrão.');
}

if (!str_contains($header, 'currentCompany:')
    || !str_contains($footer, '/js/CompanyFavicon.js')) {
    throw new RuntimeException('Contexto global não inicializa o favicon da empresa.');
}

foreach ([$financePrint, $logsPrint] as $printView) {
    if (!str_contains($printView, '/js/CompanyFavicon.js')
        || !str_contains($printView, 'currentCompany:')) {
        throw new RuntimeException('Uma view de impressão não inicializa o favicon.');
    }
}

echo "Company favicon test passed\n";
