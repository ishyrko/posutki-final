<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetOwnerListings;

use App\Application\DTO\PropertyDTO;
use App\Application\Service\PropertyOwnerPublicContactResolver;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\{
    PropertyRepositoryInterface,
    CityRepositoryInterface,
    StreetRepositoryInterface,
    MetroStationRepositoryInterface,
    PropertyMetroStationRepositoryInterface,
};
use App\Domain\Shared\Exception\NotFoundException;
use App\Domain\Shared\ValueObject\Id;

final class GetOwnerListingsHandler
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly StreetRepositoryInterface $streetRepository,
        private readonly MetroStationRepositoryInterface $metroStationRepository,
        private readonly PropertyMetroStationRepositoryInterface $propertyMetroStationRepository,
        private readonly PropertyOwnerPublicContactResolver $ownerPublicContactResolver,
    ) {
    }

    /**
     * @return list<PropertyDTO>
     */
    public function __invoke(GetOwnerListingsQuery $query): array
    {
        $propertyId = Id::fromString($query->propertyId);
        $property = $this->propertyRepository->findById($propertyId);

        if ($property === null) {
            throw new NotFoundException('Объявление не найдено');
        }

        if ($property->getStatus() !== 'published') {
            if ($query->viewerUserId === null || !$property->isOwnedBy($query->viewerUserId)) {
                throw new NotFoundException('Объявление не найдено');
            }
        }

        if ($property->getType() !== PropertyType::Apartment->value) {
            return [];
        }

        $ownerId = (string) $property->getOwnerId()->getValue();
        $properties = $this->propertyRepository->findPublishedByOwner(
            $ownerId,
            $query->limit,
            $property->getId()->getValue(),
            PropertyType::Apartment->value,
        );

        if ($properties === []) {
            return [];
        }

        $cityIds = array_unique(array_map(
            static fn($item) => $item->getCityId(),
            $properties
        ));

        $streetIds = array_filter(array_unique(array_map(
            static fn($item) => $item->getStreetId(),
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

        $propertyIds = array_map(
            static fn($item) => $item->getId()->getValue(),
            $properties
        );

        $propertyMetroStations = $this->propertyMetroStationRepository->findByPropertyIds($propertyIds);
        $metroStationIds = array_values(array_unique(array_map(
            static fn($propertyMetroStation) => $propertyMetroStation->getMetroStationId(),
            $propertyMetroStations
        )));

        $metroStationsById = [];
        foreach ($this->metroStationRepository->findByIds($metroStationIds) as $metroStation) {
            $metroStationsById[$metroStation->getId()] = $metroStation;
        }

        $nearbyMetroByPropertyId = [];
        foreach ($propertyMetroStations as $propertyMetroStation) {
            $metroStation = $metroStationsById[$propertyMetroStation->getMetroStationId()] ?? null;
            if ($metroStation === null) {
                continue;
            }

            $nearbyMetroByPropertyId[$propertyMetroStation->getPropertyId()][] = [
                'id' => $metroStation->getId(),
                'name' => $metroStation->getName(),
                'slug' => $metroStation->getSlug(),
                'line' => $metroStation->getLine(),
                'distanceKm' => $propertyMetroStation->getDistanceKm(),
            ];
        }

        foreach ($nearbyMetroByPropertyId as &$metroStations) {
            usort(
                $metroStations,
                static fn(array $a, array $b): int => $a['distanceKm'] <=> $b['distanceKm']
            );
        }
        unset($metroStations);

        $ownerContacts = $this->ownerPublicContactResolver->resolveForOwnerIds([(int) $ownerId]);
        $contact = $ownerContacts[(int) $ownerId] ?? ['phone' => null, 'name' => null, 'phones' => [], 'telegram' => null];

        return array_map(
            function ($item) use ($cities, $streets, $nearbyMetroByPropertyId, $contact) {
                return PropertyDTO::fromEntity(
                    $item,
                    $cities[$item->getCityId()] ?? null,
                    $streets[$item->getStreetId()] ?? null,
                    $nearbyMetroByPropertyId[$item->getId()->getValue()] ?? [],
                    0,
                    null,
                    $contact,
                );
            },
            $properties
        );
    }
}
