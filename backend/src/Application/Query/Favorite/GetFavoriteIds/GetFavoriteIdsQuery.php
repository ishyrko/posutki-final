<?php

declare(strict_types=1);

namespace App\Application\Query\Favorite\GetFavoriteIds;

final class GetFavoriteIdsQuery
{
    public function __construct(
        public string $userId,
    ) {
    }
}
