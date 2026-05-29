<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetHomeRegionHouseCounts;

use App\Domain\Property\Repository\PropertyRepositoryInterface;

final class GetHomeRegionHouseCountsHandler
{
    /** @var list<array{slug: string, regionSlug: string}> */
    private const HOME_REGION_FILTERS = [
        ['slug' => 'minsk', 'regionSlug' => 'minsk'],
        ['slug' => 'brest', 'regionSlug' => 'brest'],
        ['slug' => 'vitebsk', 'regionSlug' => 'vitebsk'],
        ['slug' => 'grodno', 'regionSlug' => 'grodno'],
        ['slug' => 'gomel', 'regionSlug' => 'gomel'],
        ['slug' => 'mogilev', 'regionSlug' => 'mogilev'],
    ];

    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function __invoke(GetHomeRegionHouseCountsQuery $query): array
    {
        $counts = [];

        foreach (self::HOME_REGION_FILTERS as $definition) {
            $counts[$definition['slug']] = $this->propertyRepository->count([
                'type' => 'house',
                'dealType' => 'daily',
                'regionSlug' => $definition['regionSlug'],
            ]);
        }

        return $counts;
    }
}
