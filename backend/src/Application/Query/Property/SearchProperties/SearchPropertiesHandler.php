<?php

declare(strict_types=1);

namespace App\Application\Query\Property\SearchProperties;

use App\Application\DTO\PropertyDTO;
use App\Application\Service\PropertyOwnerPublicContactResolver;
use App\Domain\Property\Repository\{
    PropertyRepositoryInterface,
    CityRepositoryInterface,
    CityDistrictRepositoryInterface,
    CityMicrodistrictRepositoryInterface,
    ResidentialComplexRepositoryInterface,
    StreetRepositoryInterface,
    MetroStationRepositoryInterface,
    PropertyMetroStationRepositoryInterface,
    LandmarkRepositoryInterface,
    PropertyLandmarkRepositoryInterface,
};
use App\Domain\Property\Service\CitiesWithDistricts;
use App\Domain\Shared\Exception\NotFoundException;
use App\Infrastructure\Service\ExchangeRateService;

final class SearchPropertiesHandler
{
    private const MINSK_CITY_SLUG = 'minsk';

    /** @var list<string> */
    private const METRO_EXCLUDED_REGION_SLUGS = ['brest', 'vitebsk', 'gomel', 'grodno', 'mogilev'];

    /** Города с отдельным URL-каталогом квартир — не показываются в региональном каталоге. */
    /** @var list<string> — по алфавиту названий, в синхроне с frontend CITY_PREFIX_SLUG_LIST. */
    private const APARTMENT_CITY_PREFIX_SLUGS = [
        'baranovichi',
        'bobruysk',
        'volkovysk',
        'zhlobin',
        'zhodino',
        'krichev',
        'logoysk',
        'molodechno',
        'novolukoml',
        'novopolotsk',
        'orsha',
        'pinsk',
        'svetlogorsk',
        'smorgon',
    ];

    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly CityDistrictRepositoryInterface $cityDistrictRepository,
        private readonly CityMicrodistrictRepositoryInterface $cityMicrodistrictRepository,
        private readonly ResidentialComplexRepositoryInterface $residentialComplexRepository,
        private readonly StreetRepositoryInterface $streetRepository,
        private readonly MetroStationRepositoryInterface $metroStationRepository,
        private readonly PropertyMetroStationRepositoryInterface $propertyMetroStationRepository,
        private readonly LandmarkRepositoryInterface $landmarkRepository,
        private readonly PropertyLandmarkRepositoryInterface $propertyLandmarkRepository,
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
        if ($query->cityDistrictSlug !== null) {
            $districtSlug = trim($query->cityDistrictSlug);
            if ($districtSlug !== '') {
                $resolvedCitySlug = $query->citySlug ?? $query->regionSlug;
                if ($resolvedCitySlug === null || trim($resolvedCitySlug) === '') {
                    throw new NotFoundException('Район не найден');
                }

                $city = $this->cityRepository->findBySlug(trim($resolvedCitySlug));
                if ($city === null || !CitiesWithDistricts::supportsSlug($city->getSlug())) {
                    throw new NotFoundException('Район не найден');
                }

                $cityDistrict = $this->cityDistrictRepository->findByCityIdAndSlug($city->getId(), $districtSlug);
                if ($cityDistrict === null) {
                    throw new NotFoundException('Район не найден');
                }

                $filters['cityDistrictId'] = $cityDistrict->getId();
            }
        }
        if ($query->microdistrictSlug !== null) {
            $microSlug = trim($query->microdistrictSlug);
            if ($microSlug !== '') {
                $resolvedCitySlug = $query->citySlug ?? $query->regionSlug;
                if ($resolvedCitySlug === null || trim($resolvedCitySlug) === '') {
                    throw new NotFoundException('Микрорайон не найден');
                }

                $city = $this->cityRepository->findBySlug(trim($resolvedCitySlug));
                if ($city === null) {
                    throw new NotFoundException('Микрорайон не найден');
                }

                $microdistrict = $this->cityMicrodistrictRepository->findByCityIdAndSlug($city->getId(), $microSlug);
                if ($microdistrict === null) {
                    throw new NotFoundException('Микрорайон не найден');
                }

                $filters['cityMicrodistrictId'] = $microdistrict->getId();
            }
        }
        if ($query->residentialComplexSlug !== null) {
            $complexSlug = trim($query->residentialComplexSlug);
            if ($complexSlug !== '') {
                $resolvedCitySlug = $query->citySlug ?? $query->regionSlug;
                if ($resolvedCitySlug === null || trim($resolvedCitySlug) === '') {
                    throw new NotFoundException('Жилой комплекс не найден');
                }

                $city = $this->cityRepository->findBySlug(trim($resolvedCitySlug));
                if ($city === null) {
                    throw new NotFoundException('Жилой комплекс не найден');
                }

                $complex = $this->residentialComplexRepository->findByCityIdAndSlug($city->getId(), $complexSlug);
                if ($complex === null) {
                    throw new NotFoundException('Жилой комплекс не найден');
                }

                $filters['residentialComplexId'] = $complex->getId();
            }
        }
        if ($query->landmarkSlug !== null) {
            $landmarkSlug = trim($query->landmarkSlug);
            if ($landmarkSlug !== '') {
                $resolvedCitySlug = $query->citySlug ?? $query->regionSlug;
                if ($resolvedCitySlug === null || trim($resolvedCitySlug) === '') {
                    throw new NotFoundException('Достопримечательность не найдена');
                }

                $city = $this->cityRepository->findBySlug(trim($resolvedCitySlug));
                if ($city === null) {
                    throw new NotFoundException('Достопримечательность не найдена');
                }

                $landmark = $this->landmarkRepository->findByCityIdAndSlug($city->getId(), $landmarkSlug);
                if ($landmark === null) {
                    throw new NotFoundException('Достопримечательность не найдена');
                }

                $filters['landmarkId'] = $landmark->getId();
            }
        }
        if ($query->maxLandmarkDistanceKm !== null && $query->maxLandmarkDistanceKm > 0) {
            $filters['maxLandmarkDistanceKm'] = $query->maxLandmarkDistanceKm;
        }
        $filterCurrency = $query->currency ?? 'BYN';
        if ($query->minPrice !== null) {
            $filters['minPriceByn'] = (int) round($this->exchangeRateService->convertToByn($query->minPrice, $filterCurrency));
        }
        if ($query->maxPrice !== null) {
            $filters['maxPriceByn'] = (int) round($this->exchangeRateService->convertToByn($query->maxPrice, $filterCurrency));
        }
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

        $cityDistrictIds = array_filter(array_unique(array_map(
            fn($p) => $p->getCityDistrictId(),
            $properties
        )));

        $cityMicrodistrictIds = array_filter(array_unique(array_map(
            fn($p) => $p->getCityMicrodistrictId(),
            $properties
        )));

        $residentialComplexIds = array_filter(array_unique(array_map(
            fn($p) => $p->getResidentialComplexId(),
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

        $cityDistricts = [];
        foreach ($cityDistrictIds as $cityDistrictId) {
            $cityDistrict = $this->cityDistrictRepository->findById($cityDistrictId);
            if ($cityDistrict !== null) {
                $cityDistricts[$cityDistrictId] = $cityDistrict;
            }
        }

        $cityMicrodistricts = [];
        foreach ($cityMicrodistrictIds as $cityMicrodistrictId) {
            $microdistrict = $this->cityMicrodistrictRepository->findById($cityMicrodistrictId);
            if ($microdistrict !== null) {
                $cityMicrodistricts[$cityMicrodistrictId] = $microdistrict;
            }
        }

        $residentialComplexes = [];
        foreach ($residentialComplexIds as $residentialComplexId) {
            $complex = $this->residentialComplexRepository->findById($residentialComplexId);
            if ($complex !== null) {
                $residentialComplexes[$residentialComplexId] = $complex;
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

        $landmarkIdForDistance = $filters['landmarkId'] ?? null;
        $landmarkDistanceByPropertyId = [];
        if ($landmarkIdForDistance !== null) {
            foreach ($this->propertyLandmarkRepository->findByPropertyIds($propertyIds) as $propertyLandmark) {
                if ($propertyLandmark->getLandmarkId() === $landmarkIdForDistance) {
                    $landmarkDistanceByPropertyId[$propertyLandmark->getPropertyId()] = $propertyLandmark->getDistanceKm();
                }
            }
        }

        $ownerIds = array_values(array_unique(array_map(
            static fn($property) => $property->getOwnerId()->getValue(),
            $properties
        )));
        $ownerContacts = $this->ownerPublicContactResolver->resolveForOwnerIds($ownerIds);

        $items = array_map(
            function ($property) use ($cities, $streets, $cityDistricts, $nearbyMetroByPropertyId, $landmarkDistanceByPropertyId, $ownerContacts) {
                $ownerId = $property->getOwnerId()->getValue();
                $contact = $ownerContacts[$ownerId] ?? ['phone' => null, 'name' => null, 'phones' => [], 'telegram' => null];

                return PropertyDTO::fromEntity(
                    $property,
                    $cities[$property->getCityId()],
                    $streets[$property->getStreetId()] ?? null,
                    $cityDistricts[$property->getCityDistrictId()] ?? null,
                    $cityMicrodistricts[$property->getCityMicrodistrictId()] ?? null,
                    $residentialComplexes[$property->getResidentialComplexId()] ?? null,
                    $nearbyMetroByPropertyId[$property->getId()->getValue()] ?? [],
                    0,
                    null,
                    $contact,
                    landmarkDistanceKm: $landmarkDistanceByPropertyId[$property->getId()->getValue()] ?? null,
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
