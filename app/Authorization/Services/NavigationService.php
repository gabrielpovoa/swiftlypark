<?php

declare(strict_types=1);

namespace App\Authorization\Services;

use App\Services\AuthorizationService;

final class NavigationService
{
    private const ITEMS = [
        ['href' => '/', 'icon' => 'home', 'label' => 'Painel Inicial', 'permission' => 'dashboard.view', 'scope' => 'tenant'],
        ['href' => '/vacancy/manage', 'icon' => 'layout-grid', 'label' => 'Gerenciar Vagas', 'permission' => 'vehicle.view', 'scope' => 'tenant'],
        ['href' => '/About', 'icon' => 'info', 'label' => 'Sobre o Projeto', 'scope' => 'global'],
        ['href' => '/Contact', 'icon' => 'mail', 'label' => 'Suporte & Contato', 'scope' => 'global'],
        ['href' => '#', 'icon' => 'printer', 'label' => 'Relatórios', 'permission' => 'report.view', 'class' => 'js-print-logs', 'id' => 'btn-print-logs', 'scope' => 'tenant'],
        ['href' => '/audit', 'icon' => 'search-check', 'label' => 'Auditoria', 'permission' => 'audit.view', 'scope' => 'tenant'],
        ['href' => '/identity', 'icon' => 'users-round', 'label' => 'Usuários', 'permission' => 'identity.view', 'scope' => 'platform'],
        ['href' => '/admin/companies', 'icon' => 'building-2', 'label' => 'Empresas', 'permission' => 'identity.manage', 'role' => 'super-admin', 'scope' => 'platform'],
        ['href' => '/admin', 'icon' => 'shield-check', 'label' => 'Governança SaaS', 'permission' => 'identity.manage', 'scope' => 'platform'],
        ['href' => '/finance', 'icon' => 'chart-column', 'label' => 'Relatórios Financeiros', 'permission' => 'finance.view', 'scope' => 'tenant'],
    ];

    public function __construct(private AuthorizationService $authorization)
    {
    }

    public function defaultItems(): array
    {
        return self::ITEMS;
    }

    public function allowedItems(array $items): array
    {
        return array_values(array_filter(
            $items,
            fn (array $item): bool => !isset($item['permission'])
                || ($this->authorization->can($item['permission'])
                    && (!isset($item['role'])
                        || $this->authorization->hasAnyRole([$item['role']])))
        ));
    }
}
