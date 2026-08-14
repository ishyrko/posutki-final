<?php

declare(strict_types=1);

namespace App\Application\Query\Favorite\GetFavorites;

use App\Application\DTO\PropertyDTO;
use App\Application\Service\PropertyOwnerPublicContactResolver;
use App\Domain\Favorite\Repository\FavoriteRepositoryInterface;
use App\Domain\Property\Repository\{PropertyRepositoryInterface, CityRepositoryInterface, CityDistrictRepositoryInterface, StreetRepositoryInterface};
use App\Domain\Shared\ValueObject\Id;

final class GetFavoritesHandler
{
    public function __construct(
        private FavoriteRepositoryInterface $favoriteRepository,
        private PropertyRepositoryInterface $propertyRepository,
        private CityRepositoryInterface $cityRepository,
        private CityDistrictRepositoryInterface $cityDistrictRepository,
        private StreetRepositoryInterface $streetRepository,
        private PropertyOwnerPublicContactResolver $ownerPublicContactResolver,
    ) {
    }

    public function __invoke(GetFavoritesQuery $query): array
    {
        $userId = Id::fromString($query->userId);
        $favorites = $this->favoriteRepository->findByUser($userId, $query->page, $query->limit);

        $rows = [];
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

            $cityDistrict = null;
            if ($property->getCityDistrictId() !== null) {
                $cityDistrict = $this->cityDistrictRepository->findById($property->getCityDistrictId());
            }

            $rows[] = ['property' => $property, 'city' => $city, 'street' => $street, 'cityDistrict' => $cityDistrict];
        }

        $ownerIds = array_values(array_unique(array_map(
            static fn(array $r) => $r['property']->getOwnerId()->getValue(),
            $rows
        )));
        $ownerContacts = $this->ownerPublicContactResolver->resolveForOwnerIds($ownerIds);

        return array_map(
            function (array $r) use ($ownerContacts) {
                $ownerId = $r['property']->getOwnerId()->getValue();
                $contact = $ownerContacts[$ownerId] ?? ['phone' => null, 'name' => null, 'phones' => [], 'telegram' => null];

                return PropertyDTO::fromEntity(
                    $r['property'],
                    $r['city'],
                    $r['street'],
                    $r['cityDistrict'],
                    null,
                    null,
                    [],
                    0,
                    null,
                    $contact,
                );
            },
            $rows
        );
    }
}
