<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetMyProperties;

use App\Application\DTO\PropertyDTO;
use App\Domain\Favorite\Repository\FavoriteRepositoryInterface;
use App\Domain\Property\Repository\{PropertyRepositoryInterface, CityRepositoryInterface, StreetRepositoryInterface};

final class GetMyPropertiesHandler
{
    public function __construct(
        private PropertyRepositoryInterface $propertyRepository,
        private CityRepositoryInterface $cityRepository,
        private StreetRepositoryInterface $streetRepository,
        private FavoriteRepositoryInterface $favoriteRepository,
    ) {
    }

    public function __invoke(GetMyPropertiesQuery $query): array
    {
        $properties = $this->propertyRepository->findByOwner(
            $query->userId,
            $query->page,
            $query->limit
        );

        $cityIds = array_unique(array_map(
            fn($p) => $p->getCityId(),
            $properties
        ));

        $streetIds = array_filter(array_unique(array_map(
            fn($p) => $p->getStreetId(),
            $properties
        )));

        $cities = [];
        foreach ($cityIds as $cityId) {
            $city = $this->cityRepository->findById($cityId);
            if ($city !== null) {
                $cities[$cityId] = $city;
            }
        }

        $streets = [];
        foreach ($streetIds as $streetId) {
            $street = $this->streetRepository->findById($streetId);
            if ($street !== null) {
                $streets[$streetId] = $street;
            }
        }

        return array_map(
            fn($property) => PropertyDTO::fromEntity(
                $property,
                $cities[$property->getCityId()] ?? null,
                $streets[$property->getStreetId()] ?? null,
                [],
                $this->favoriteRepository->countByProperty($property->getId())
            ),
            $properties
        );
    }
}
