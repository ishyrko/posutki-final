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
    private const MINSK_CITY_SLUG = 'minsk';

    /** @var list<string> */
    private const METRO_EXCLUDED_REGION_SLUGS = ['brest', 'vitebsk', 'gomel', 'grodno', 'mogilev'];

    /** Города с отдельным URL-каталогом квартир — не показываются в региональном каталоге. */
    /** @var list<string> */
    private const APARTMENT_CITY_PREFIX_SLUGS = [
        'orsha',
        'svetlogorsk',
        'smorgon',
        'molodechno',
        'baranovichi',
        'pinsk',
        'novopolotsk',
        'bobruysk',
    ];

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
        if ($query->rooms !== null && $query->rooms !== []) {
            $filters['rooms'] = $query->rooms;
        }
        if ($this->shouldApplyMetroFilters($query)) {
            if ($query->metroStationId !== null) {
                $filters['metroStationId'] = $query->metroStationId;
            }
            if ($query->nearMetro) {
                $filters['nearMetro'] = true;
            }
        }
        if ($query->guests !== null && $query->guests > 0) {
            $filters['minGuests'] = $query->guests;
        }

        if ($this->shouldExcludeCityPrefixFromRegionSearch($query)) {
            $filters['excludeCitySlugs'] = self::APARTMENT_CITY_PREFIX_SLUGS;
        }

        $filters['sortBy'] = $query->sortBy;
        $filters['sortOrder'] = strtoupper($query->sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $total = $this->propertyRepository->count($filters);

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

        $items = array_map(
            function ($property) use ($cities, $streets, $nearbyMetroByPropertyId, $ownerContacts) {
                $ownerId = $property->getOwnerId()->getValue();
                $contact = $ownerContacts[$ownerId] ?? ['phone' => null, 'name' => null, 'phones' => [], 'telegram' => null];

                return PropertyDTO::fromEntity(
                    $property,
                    $cities[$property->getCityId()],
                    $streets[$property->getStreetId()] ?? null,
                    $nearbyMetroByPropertyId[$property->getId()->getValue()] ?? [],
                    0,
                    null,
                    $contact,
                );
            },
            $properties
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $query->page,
            'limit' => $query->limit,
        ];
    }

    private function shouldApplyMetroFilters(SearchPropertiesQuery $query): bool
    {
        if ($query->metroStationId === null && !$query->nearMetro) {
            return false;
        }

        if (!$this->isApartmentMetroSearch($query)) {
            return false;
        }

        if ($query->regionSlug !== null && \in_array($query->regionSlug, self::METRO_EXCLUDED_REGION_SLUGS, true)) {
            return false;
        }

        if ($query->citySlug !== null && $query->citySlug !== self::MINSK_CITY_SLUG) {
            return false;
        }

        return true;
    }

    private function isApartmentMetroSearch(SearchPropertiesQuery $query): bool
    {
        if ($query->types !== null && $query->types !== []) {
            return $query->types === ['apartment'];
        }

        if ($query->type !== null) {
            return $query->type === 'apartment';
        }

        return $query->nearMetro || $query->metroStationId !== null;
    }

    private function shouldExcludeCityPrefixFromRegionSearch(SearchPropertiesQuery $query): bool
    {
        return $query->regionSlug !== null
            && $query->citySlug === null
            && $this->isApartmentOnlySearch($query);
    }

    private function isApartmentOnlySearch(SearchPropertiesQuery $query): bool
    {
        if ($query->types !== null && $query->types !== []) {
            return $query->types === ['apartment'];
        }

        if ($query->type !== null) {
            return $query->type === 'apartment';
        }

        return false;
    }
}
