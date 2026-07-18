<?php

declare(strict_types=1);

namespace App\Companies\Domain;

use DomainException;

final class Company
{
    private function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $slug,
        private readonly ?string $logoPath,
        private readonly bool $active
    ) {
    }

    public static function register(string $name, string $slug, ?string $logoPath = null): self
    {
        $name = trim($name);
        $slug = strtolower(trim($slug));
        if ($name === '' || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
            throw new DomainException('Nome e slug válido são obrigatórios.');
        }

        return new self(0, $name, $slug, $logoPath, true);
    }

    public static function reconstitute(
        int $id,
        string $name,
        string $slug,
        ?string $logoPath,
        bool $active
    ): self {
        return new self($id, $name, $slug, $logoPath, $active);
    }

    public function id(): int { return $this->id; }
    public function name(): string { return $this->name; }
    public function slug(): string { return $this->slug; }
    public function logoPath(): ?string { return $this->logoPath; }
    public function isActive(): bool { return $this->active; }
}
