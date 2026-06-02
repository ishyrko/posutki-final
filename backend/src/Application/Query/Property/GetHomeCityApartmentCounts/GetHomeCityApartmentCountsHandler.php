<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetHomeCityApartmentCounts;

use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;

final class GetHomeCityApartmentCountsHandler
{
    private const MINSK_REGION_SLUG = 'minsk';

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
        'zhlobin',
        'volkovysk',
    ];

    /** @var list<array{slug: string, regionSlug?: string, citySlug?: string}> */
    private const HOME_CITY_FILTERS = [
        ['slug' => self::MINSK_REGION_SLUG, 'regionSlug' => self::MINSK_REGION_SLUG],
        ['slug' => 'brest', 'regionSlug' => 'brest'],
        ['slug' => 'vitebsk', 'regionSlug' => 'vitebsk'],
        ['slug' => 'grodno', 'regionSlug' => 'grodno'],
        ['slug' => 'gomel', 'regionSlug' => 'gomel'],
        ['slug' => 'mogilev', 'regionSlug' => 'mogilev'],
        ['slug' => 'baranovichi', 'citySlug' => 'baranovichi'],
        ['slug' => 'pinsk', 'citySlug' => 'pinsk'],
        ['slug' => 'bobruysk', 'citySlug' => 'bobruysk'],
        ['slug' => 'molodechno', 'citySlug' => 'molodechno'],
        ['slug' => 'orsha', 'citySlug' => 'orsha'],
        ['slug' => 'novopolotsk', 'citySlug' => 'novopolotsk'],
        ['slug' => 'svetlogorsk', 'citySlug' => 'svetlogorsk'],
        ['slug' => 'smorgon', 'citySlug' => 'smorgon'],
        ['slug' => 'zhlobin', 'citySlug' => 'zhlobin'],
        ['slug' => 'volkovysk', 'citySlug' => 'volkovysk'],
    ];

    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly CityRepositoryInterface $cityRepository,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function __invoke(GetHomeCityApartmentCountsQuery $query): array
    {
        $counts = [];

        foreach (self::HOME_CITY_FILTERS as $definition) {
            $slug = $definition['slug'];
            $filters = [
                'type' => 'apartment',
                'dealType' => 'daily',
            ];

            if (isset($definition['citySlug'])) {
                if ($this->cityRepository->findBySlug($definition['citySlug']) === null) {
                    $counts[$slug] = 0;
                    continue;
                }
                $filters['citySlug'] = $definition['citySlug'];
            } else {
                $filters['regionSlug'] = $definition['regionSlug'];
                $filters['excludeCitySlugs'] = self::APARTMENT_CITY_PREFIX_SLUGS;
            }

            $counts[$slug] = $this->propertyRepository->count($filters);
        }

        return $counts;
    }
}
