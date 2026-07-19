<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\AuditLogPresenter;

$presenter = new AuditLogPresenter();
$baseLog = [
    'id' => 521,
    'user_id' => 1,
    'company_id' => null,
    'company_name' => null,
    'actor_email' => 'admin@example.com',
    'actor_name' => 'Administrador',
    'action' => 'UPDATE',
    'entity' => 'global_dashboard',
    'entity_id' => 'admin/dashboard',
    'old_values' => null,
    'new_values' => json_encode([
        'event' => 'GLOBAL_DASHBOARD_ACCESS',
        'role_slugs' => ['super-admin'],
    ], JSON_THROW_ON_ERROR),
    'ip_address' => '172.18.0.1',
    'request_id' => 'e21917f5-2f49-4406-81a0-fbeaef236dd9',
    'created_at' => '2026-07-19 13:17:45',
];

$global = $presenter->present($baseLog);

if ($global['event_label'] !== 'Acesso ao painel global') {
    throw new RuntimeException('O código do evento global não foi traduzido.');
}

if ($global['description'] !== 'O painel consolidado de todas as empresas foi acessado.') {
    throw new RuntimeException('A descrição operacional do painel global está incorreta.');
}

if ($global['changes'] !== []) {
    throw new RuntimeException('Metadados técnicos não devem aparecer como alterações operacionais.');
}

$technical = array_column($global['technical_details'], 'value', 'label');
if (($technical['Página acessada'] ?? null) !== '/admin/dashboard'
    || ($technical['Endereço IP'] ?? null) !== '172.18.0.1'
    || ($technical['E-mail'] ?? null) !== 'admin@example.com') {
    throw new RuntimeException('O contexto técnico do evento está incompleto.');
}

$support = $presenter->present([
    ...$baseLog,
    'id' => 520,
    'company_id' => 9,
    'company_name' => 'SafePark',
    'entity' => 'support_impersonation',
    'entity_id' => '9',
    'new_values' => json_encode([
        'event' => 'IMPERSONATION_STARTED',
        'target_company_id' => 9,
        'target_company_name' => 'SafePark',
        'simulated_role_label' => 'Super-Admin (sem simulação)',
        'user_agent' => 'Firefox Test',
    ], JSON_THROW_ON_ERROR),
]);

if ($support['event_label'] !== 'Modo suporte iniciado'
    || str_contains(json_encode($support['changes'], JSON_THROW_ON_ERROR), 'Não informado')) {
    throw new RuntimeException('O evento de suporte ainda expõe comparação sem valor anterior.');
}

$supportTechnical = array_column($support['technical_details'], 'value', 'label');
if (($supportTechnical['Empresa'] ?? null) !== 'SafePark'
    || ($supportTechnical['Navegador'] ?? null) !== 'Firefox Test') {
    throw new RuntimeException('Empresa ou navegador ausente nos detalhes técnicos.');
}

echo "Audit log presenter test passed\n";
