<?php

declare(strict_types=1);

namespace App\Security;

final class InputSanitizer
{
    public function text(mixed $value, int $maxLength = 255): string
    {
        $text = trim(strip_tags((string) $value));
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';

        return mb_substr($text, 0, max(0, $maxLength));
    }

    public function payload(mixed $value): mixed
    {
        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $safeKey = is_string($key) ? $this->text($key, 100) : $key;
                $sanitized[$safeKey] = $this->payload($item);
            }

            return $sanitized;
        }

        return is_string($value) ? $this->text($value, 2000) : $value;
    }
}
