<?php

declare(strict_types=1);

namespace App\Application\Command\Favorite\AddFavorite;

use App\Domain\Favorite\Entity\Favorite;
use App\Domain\Favorite\Repository\FavoriteRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;

readonly class AddFavoriteHandler
{
    public function __construct(
        private FavoriteRepositoryInterface $favoriteRepository,
        private PropertyRepositoryInterface $propertyRepository,
    ) {
    }

    public function __invoke(AddFavoriteCommand $command): void
    {
        $userId = Id::fromString($command->userId);
        $propertyId = Id::fromString($command->propertyId);

        $property = $this->propertyRepository->findById($propertyId);
        if (!$property) {
            throw new \InvalidArgumentException('Объявление не найдено');
        }

        $existing = $this->favoriteRepository->findByUserAndProperty($userId, $propertyId);
        if ($existing) {
            return;
        }

        $favorite = new Favorite($userId, $propertyId);
        $this->favoriteRepository->save($favorite);
    }
}
