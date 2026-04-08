<?php

declare(strict_types=1);

namespace App\Application\Query\Favorite\GetFavorites;

final class GetFavoritesQuery
{
    public function __construct(
        public string $userId,
        public int $page = 1,
        public int $limit = 20,
    ) {
    }
}
