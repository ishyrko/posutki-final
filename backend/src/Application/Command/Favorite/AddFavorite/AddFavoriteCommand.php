<?php

declare(strict_types=1);

namespace App\Application\Command\Favorite\AddFavorite;

readonly class AddFavoriteCommand
{
    public function __construct(
        public string $userId,
        public string $propertyId,
    ) {
    }
}
