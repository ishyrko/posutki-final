<?php

declare(strict_types=1);

namespace App\Application\Query\Favorite\GetFavoriteIds;

use App\Domain\Favorite\Repository\FavoriteRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;

final class GetFavoriteIdsHandler
{
    public function __construct(
        private FavoriteRepositoryInterface $favoriteRepository,
    ) {
    }

    /** @return int[] */
    public function __invoke(GetFavoriteIdsQuery $query): array
    {
        return $this->favoriteRepository->findPropertyIdsByUser(
            Id::fromString($query->userId)
        );
    }
}
