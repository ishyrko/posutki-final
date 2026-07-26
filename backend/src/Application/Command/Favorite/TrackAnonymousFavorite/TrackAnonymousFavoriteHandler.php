<?php

declare(strict_types=1);

namespace App\Application\Command\Favorite\TrackAnonymousFavorite;

use App\Domain\Favorite\Entity\Favorite;
use App\Domain\Favorite\Repository\FavoriteRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;

readonly class TrackAnonymousFavoriteHandler
{
    public function __construct(
        private FavoriteRepositoryInterface $favoriteRepository,
        private PropertyRepositoryInterface $propertyRepository,
    ) {
    }

    public function __invoke(TrackAnonymousFavoriteCommand $command): void
    {
        $visitorId = Favorite::normalizeVisitorId($command->visitorId);
        $propertyId = Id::fromString($command->propertyId);

        $property = $this->propertyRepository->findById($propertyId);
        if ($property === null || $property->getStatus() !== 'published') {
            throw new \InvalidArgumentException('Объявление не найдено');
        }

        if ($this->favoriteRepository->findByVisitorAndProperty($visitorId, $propertyId) !== null) {
            return;
        }

        $this->favoriteRepository->save(Favorite::forVisitor($visitorId, $propertyId));
    }
}
