<?php

declare(strict_types=1);

namespace App\Application\Command\Favorite\RemoveAnonymousFavorite;

readonly class RemoveAnonymousFavoriteCommand
{
    public function __construct(
        public string $visitorId,
        public string $propertyId,
    ) {
    }
}
