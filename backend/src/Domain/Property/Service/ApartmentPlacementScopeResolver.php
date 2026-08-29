<?php

declare(strict_types=1);

namespace App\Domain\Property\Service;

use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\Region;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementLevelPriceRepositoryInterface;
use App\Domain\Property\ValueObject\ApartmentPlacementScope;

class ApartmentPlacementScopeResolver
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
        private readonly PropertyPlacementLevelPriceRepositoryInterface $levelPriceRepository,
    ) {
    }

    public function resolveForCityId(int $cityId): ?ApartmentPlacementScope
    {
        $city = $this->cityRepository->findById($cityId);

        return $city !== null ? $this->resolve($city) : null;
    }

    public function resolve(City $city): ?ApartmentPlacementScope
    {
        $region = $city->getRegionDistrict()?->getRegion();
        if ($region === null) {
            return ApartmentPlacementScope::forCity($city->getId(), $city->getName());
        }

        if ($this->hasOwnTariffs($city) && $city->isApartmentCatalog() && !$city->isMain()) {
            return ApartmentPlacementScope::forCity($city->getId(), $city->getName());
        }

        $tariffCity = $city->isMain()
            ? $city
            : $this->cityRepository->findMainCityByRegionId($region->getId());

        if ($tariffCity === null || !$this->hasOwnTariffs($tariffCity)) {
            return ApartmentPlacementScope::forCity($city->getId(), $city->getName());
        }

        return ApartmentPlacementScope::forRegion(
            tariffCityId: $tariffCity->getId(),
            locationLabel: $this->formatRegionalLabel($tariffCity),
            catalogRegionId: $region->getId(),
            excludeCitySlugs: $this->prefixCitySlugsInRegion($region),
        );
    }

    private function hasOwnTariffs(City $city): bool
    {
        return $this->levelPriceRepository->findActiveByCityId($city->getId()) !== [];
    }

    /**
     * @return list<string>
     */
    private function prefixCitySlugsInRegion(Region $region): array
    {
        $slugs = [];
        foreach ($this->cityRepository->findApartmentCatalog() as $catalogCity) {
            if ($catalogCity->isMain()) {
                continue;
            }

            $catalogRegion = $catalogCity->getRegionDistrict()?->getRegion();
            if ($catalogRegion !== null && $catalogRegion->getId() === $region->getId()) {
                $slugs[] = $catalogCity->getSlug();
            }
        }

        return $slugs;
    }

    private function formatRegionalLabel(City $mainCity): string
    {
        return $mainCity->getName() . ' и район';
    }
}
