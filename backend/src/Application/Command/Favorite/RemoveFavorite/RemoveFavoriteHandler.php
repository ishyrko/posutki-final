<?php

declare(strict_types=1);

namespace App\Application\Command\Favorite\RemoveFavorite;

use App\Domain\Favorite\Repository\FavoriteRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;

readonly class RemoveFavoriteHandler
{
    public function __construct(
        private FavoriteRepositoryInterface $favoriteRepository,
    ) {
    }

    public function __invoke(RemoveFavoriteCommand $command): void
    {
        $userId = Id::fromString($command->userId);
        $propertyId = Id::fromString($command->propertyId);

        $existing = $this->favoriteRepository->findByUserAndProperty($userId, $propertyId);
        if (!$existing) {
            return;
        }

        $this->favoriteRepository->delete($existing);
    }
}
