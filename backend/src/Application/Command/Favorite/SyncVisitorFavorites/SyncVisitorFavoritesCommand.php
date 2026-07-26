<?php

declare(strict_types=1);

namespace App\Application\Command\Favorite\SyncVisitorFavorites;

readonly class SyncVisitorFavoritesCommand
{
    /**
     * @param list<int> $propertyIds
     */
    public function __construct(
        public string $userId,
        public string $visitorId,
        public array $propertyIds,
    ) {
    }
}
