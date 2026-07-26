<?php

declare(strict_types=1);

namespace App\Application\Command\Favorite\SyncVisitorFavorites;

use App\Domain\Favorite\Entity\Favorite;
use App\Domain\Favorite\Repository\FavoriteRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;

readonly class SyncVisitorFavoritesHandler
{
    public function __construct(
        private FavoriteRepositoryInterface $favoriteRepository,
        private PropertyRepositoryInterface $propertyRepository,
    ) {
    }

    public function __invoke(SyncVisitorFavoritesCommand $command): void
    {
        $userId = Id::fromString($command->userId);
        $visitorId = Favorite::normalizeVisitorId($command->visitorId);

        foreach ($command->propertyIds as $propertyIdValue) {
            if (!is_int($propertyIdValue) || $propertyIdValue <= 0) {
                continue;
            }

            $propertyId = Id::fromInt($propertyIdValue);
            $property = $this->propertyRepository->findById($propertyId);
            if ($property === null) {
                $this->favoriteRepository->deleteByVisitorAndProperty($visitorId, $propertyId);
                continue;
            }

            if ($this->favoriteRepository->findByUserAndProperty($userId, $propertyId) === null) {
                $this->favoriteRepository->save(Favorite::forUser($userId, $propertyId));
            }

            $this->favoriteRepository->deleteByVisitorAndProperty($visitorId, $propertyId);
        }
    }
}
