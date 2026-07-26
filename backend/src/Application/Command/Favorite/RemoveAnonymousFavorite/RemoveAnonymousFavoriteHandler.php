<?php

declare(strict_types=1);

namespace App\Application\Command\Favorite\RemoveAnonymousFavorite;

use App\Domain\Favorite\Entity\Favorite;
use App\Domain\Favorite\Repository\FavoriteRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;

readonly class RemoveAnonymousFavoriteHandler
{
    public function __construct(
        private FavoriteRepositoryInterface $favoriteRepository,
    ) {
    }

    public function __invoke(RemoveAnonymousFavoriteCommand $command): void
    {
        $visitorId = Favorite::normalizeVisitorId($command->visitorId);
        $propertyId = Id::fromString($command->propertyId);

        $this->favoriteRepository->deleteByVisitorAndProperty($visitorId, $propertyId);
    }
}
