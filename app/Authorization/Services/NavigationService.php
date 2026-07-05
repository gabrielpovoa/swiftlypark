<?php

declare(strict_types=1);

namespace App\Authorization\Services;

use App\Services\AuthorizationService;

final class NavigationService
{
    public function __construct(private AuthorizationService $authorization)
    {
    }

    public function allowedItems(array $items): array
    {
        return array_values(array_filter(
            $items,
            fn (array $item): bool => !isset($item['permission'])
                || $this->authorization->can($item['permission'])
        ));
    }
}
