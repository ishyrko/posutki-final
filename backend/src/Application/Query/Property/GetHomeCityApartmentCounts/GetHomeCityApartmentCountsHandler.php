<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetHomeCityApartmentCounts;

use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;

final class GetHomeCityApartmentCountsHandler
{
    private const MINSK_REGION_SLUG = 'minsk';

    /** @var list<string> — по алфавиту названий, в синхроне с frontend CITY_PREFIX_SLUG_LIST. */
    private const APARTMENT_CITY_PREFIX_SLUGS = [
        'baranovichi',
        'bobruysk',
        'volkovysk',
        'glubokoe',
        'zhlobin',
        'zhodino',
        'krichev',
        'logoysk',
        'molodechno',
        'nesvizh',
        'novolukoml',
        'novopolotsk',
        'orsha',
        'pinsk',
        'svetlogorsk',
        'smorgon',
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
        ['slug' => 'bobruysk', 'citySlug' => 'bobruysk'],
        ['slug' => 'volkovysk', 'citySlug' => 'volkovysk'],
        ['slug' => 'glubokoe', 'citySlug' => 'glubokoe'],
        ['slug' => 'zhlobin', 'citySlug' => 'zhlobin'],
        ['slug' => 'zhodino', 'citySlug' => 'zhodino'],
        ['slug' => 'krichev', 'citySlug' => 'krichev'],
        ['slug' => 'logoysk', 'citySlug' => 'logoysk'],
        ['slug' => 'molodechno', 'citySlug' => 'molodechno'],
        ['slug' => 'nesvizh', 'citySlug' => 'nesvizh'],
        ['slug' => 'novolukoml', 'citySlug' => 'novolukoml'],
        ['slug' => 'novopolotsk', 'citySlug' => 'novopolotsk'],
        ['slug' => 'orsha', 'citySlug' => 'orsha'],
        ['slug' => 'pinsk', 'citySlug' => 'pinsk'],
        ['slug' => 'svetlogorsk', 'citySlug' => 'svetlogorsk'],
        ['slug' => 'smorgon', 'citySlug' => 'smorgon'],
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
