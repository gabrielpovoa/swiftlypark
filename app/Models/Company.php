<?php

declare(strict_types=1);

namespace App\Models;

final class Company
{
    public function __construct(
        private int $id,
        private string $name,
        private string $slug,
        private ?string $logoPath = null
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function logoPath(): ?string
    {
        return $this->logoPath;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_path' => $this->logoPath,
        ];
    }
}
