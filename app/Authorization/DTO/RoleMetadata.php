<?php

declare(strict_types=1);

namespace App\Authorization\DTO;

final class RoleMetadata
{
    private const ALLOWED_ICONS = [
        'shield-check',
        'clipboard-check',
        'search-check',
        'user',
    ];

    public function __construct(
        private string $role,
        private string $label,
        string $icon
    ) {
        $this->icon = in_array($icon, self::ALLOWED_ICONS, true)
            ? $icon
            : 'user';
    }

    private string $icon;

    public function role(): string
    {
        return $this->role;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function icon(): string
    {
        return $this->icon;
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'label' => $this->label,
            'icon' => $this->icon,
        ];
    }
}
