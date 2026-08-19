<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetHomeCityApartmentCounts;

use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;

final class GetHomeCityApartmentCountsHandler
{
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
        $catalogCities = $this->cityRepository->findApartmentCatalog();
        if ($catalogCities === []) {
            return [];
        }

        $prefixSlugs = [];
        $regionSlugs = [];

        foreach ($catalogCities as $city) {
            if ($city->isMain()) {
                $regionSlugs[] = $city->getSlug();
                continue;
            }

            $prefixSlugs[] = $city->getSlug();
        }

        $regionalCounts = $this->propertyRepository->countApartmentsGroupedByRegionSlugsExcludingCitySlugs(
            $regionSlugs,
            $prefixSlugs,
        );
        $cityCounts = $this->propertyRepository->countApartmentsGroupedByCitySlugs($prefixSlugs);

        $counts = [];
        foreach ($catalogCities as $city) {
            $slug = $city->getSlug();
            $counts[$slug] = $city->isMain()
                ? ($regionalCounts[$slug] ?? 0)
                : ($cityCounts[$slug] ?? 0);
        }

        return $counts;
    }
}
