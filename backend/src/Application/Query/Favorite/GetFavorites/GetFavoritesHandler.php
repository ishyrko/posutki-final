<?php

declare(strict_types=1);

namespace App\Application\Query\Favorite\GetFavorites;

use App\Application\DTO\PropertyDTO;
use App\Domain\Favorite\Repository\FavoriteRepositoryInterface;
use App\Domain\Property\Repository\{PropertyRepositoryInterface, CityRepositoryInterface, StreetRepositoryInterface};
use App\Domain\Shared\ValueObject\Id;

final class GetFavoritesHandler
{
    public function __construct(
        private FavoriteRepositoryInterface $favoriteRepository,
        private PropertyRepositoryInterface $propertyRepository,
        private CityRepositoryInterface $cityRepository,
        private StreetRepositoryInterface $streetRepository,
    ) {
    }

    public function __invoke(GetFavoritesQuery $query): array
    {
        $userId = Id::fromString($query->userId);
        $favorites = $this->favoriteRepository->findByUser($userId, $query->page, $query->limit);

        $properties = [];
        foreach ($favorites as $favorite) {
            $property = $this->propertyRepository->findById($favorite->getPropertyId());
            if ($property === null) {
                continue;
            }

            $city = $this->cityRepository->findById($property->getCityId());
            if ($city === null) {
                continue;
            }

            $street = null;
            if ($property->getStreetId() !== null) {
                $street = $this->streetRepository->findById($property->getStreetId());
            }

            $properties[] = PropertyDTO::fromEntity($property, $city, $street);
        }

        return $properties;
    }
}
