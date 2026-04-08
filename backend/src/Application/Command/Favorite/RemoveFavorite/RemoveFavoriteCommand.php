<?php

declare(strict_types=1);

namespace App\Application\Command\Favorite\RemoveFavorite;

readonly class RemoveFavoriteCommand
{
    public function __construct(
        public string $userId,
        public string $propertyId,
    ) {
    }
}
