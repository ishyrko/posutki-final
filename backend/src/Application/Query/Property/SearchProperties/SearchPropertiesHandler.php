<?php

declare(strict_types=1);

namespace App\Application\Query\Property\SearchProperties;

use App\Application\DTO\PropertyDTO;
use App\Application\Service\PropertyOwnerPublicContactResolver;
use App\Domain\Property\Repository\{
    PropertyRepositoryInterface,
    CityRepositoryInterface,
    StreetRepositoryInterface,
    MetroStationRepositoryInterface,
    PropertyMetroStationRepositoryInterface
};
use App\Domain\Shared\Exception\NotFoundException;
use App\Infrastructure\Service\ExchangeRateService;

final class SearchPropertiesHandler
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly StreetRepositoryInterface $streetRepository,
        private readonly MetroStationRepositoryInterface $metroStationRepository,
        private readonly PropertyMetroStationRepositoryInterface $propertyMetroStationRepository,
        private readonly ExchangeRateService $exchangeRateService,
        private readonly PropertyOwnerPublicContactResolver $ownerPublicContactResolver,
    ) {
    }

    public function __invoke(SearchPropertiesQuery $query): array
    {
        $filters = [];

        if ($query->types !== null && $query->types !== []) {
            $filters['types'] = $query->types;
        } elseif ($query->type !== null) {
            $filters['type'] = $query->type;
        }
        if ($query->dealType !== null) {
            $filters['dealType'] = $query->dealType;
        }
        if ($query->regionSlug !== null) {
            $filters['regionSlug'] = $query->regionSlug;
        }
        if ($query->citySlug !== null) {
            $citySlug = trim($query->citySlug);
            if ($citySlug !== '') {
                if ($this->cityRepository->findBySlug($citySlug) === null) {
                    throw new NotFoundException('Город не найден');
                }
                $filters['citySlug'] = $citySlug;
            }
        }
        if ($query->cityId !== null) {
            $filters['cityId'] = $query->cityId;
        }
        $filterCurrency = $query->currency ?? 'BYN';
        if ($query->minPrice !== null) {
            $filters['minPriceByn'] = (int) round($this->exchangeRateService->convertToByn($query->minPrice, $filterCurrency));
        }
        if ($query->maxPrice !== null) {
            $filters['maxPriceByn'] = (int) round($this->exchangeRateService->convertToByn($query->maxPrice, $filterCurrency));
        }
        $filters['priceType'] = $query->priceType;
        if ($query->minArea !== null) {
            $filters['minArea'] = $query->minArea;
        }
        if ($query->maxArea !== null) {
            $filters['maxArea'] = $query->maxArea;
        }
        if ($query->rooms !== null) {
            $filters['rooms'] = $query->rooms;
        }
        if ($query->metroStationId !== null) {
            $filters['metroStationId'] = $query->metroStationId;
        }
        if ($query->nearMetro) {
            $filters['nearMetro'] = true;
        }

        $filters['sortBy'] = $query->sortBy;
        $filters['sortOrder'] = strtoupper($query->sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $properties = $this->propertyRepository->findPublished(
            $filters,
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

        $propertyIds = array_map(
            static fn($property) => $property->getId()->getValue(),
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

        $ownerIds = array_values(array_unique(array_map(
            static fn($property) => $property->getOwnerId()->getValue(),
            $properties
        )));
        $ownerContacts = $this->ownerPublicContactResolver->resolveForOwnerIds($ownerIds);

        return array_map(
            function ($property) use ($cities, $streets, $nearbyMetroByPropertyId, $ownerContacts) {
                $ownerId = $property->getOwnerId()->getValue();
                $contact = $ownerContacts[$ownerId] ?? ['phone' => null, 'name' => null];

                return PropertyDTO::fromEntity(
                    $property,
                    $cities[$property->getCityId()],
                    $streets[$property->getStreetId()] ?? null,
                    $nearbyMetroByPropertyId[$property->getId()->getValue()] ?? [],
                    0,
                    null,
                    $contact['phone'],
                    $contact['name'],
                );
            },
            $properties
        );
    }
}
