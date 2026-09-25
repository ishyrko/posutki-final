<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Service\PolygonDistance;

final class PropertyCityDistanceCalculator
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
        private readonly CityBoundaryProvider $cityBoundaryProvider,
    ) {
    }

    public function syncForProperty(Property $property, ?City $propertyCity = null): void
    {
        $latitude = $property->getLatitude();
        $longitude = $property->getLongitude();

        $nearestCityId = null;
        $nearestDistanceKm = null;
        $minDistance = INF;

        foreach ($this->cityRepository->findAllCities() as $city) {
            $boundary = $this->cityBoundaryProvider->getBySlug($city->getSlug());
            if ($boundary === null) {
                continue;
            }

            $distanceKm = PolygonDistance::distanceKm($latitude, $longitude, $boundary['rings']);
            if ($distanceKm < $minDistance) {
                $minDistance = $distanceKm;
                $nearestCityId = $city->getId();
                $nearestDistanceKm = round($distanceKm, 3);
            }
        }

        $property->setNearestCityId($nearestCityId);
        $property->setNearestCityDistanceKm($nearestDistanceKm);

        if ($propertyCity === null) {
            $propertyCity = $this->cityRepository->findByIdWithRegionChain($property->getCityId());
        }

        $regionCenterCityId = null;
        $regionCenterDistanceKm = null;
        $regionCenter = $propertyCity?->getRegionDistrict()?->getRegion()?->getCenterCity();
        if ($regionCenter !== null) {
            $boundary = $this->cityBoundaryProvider->getBySlug($regionCenter->getSlug());
            if ($boundary !== null) {
                $distanceKm = PolygonDistance::distanceKm($latitude, $longitude, $boundary['rings']);
                $regionCenterCityId = $regionCenter->getId();
                $regionCenterDistanceKm = round($distanceKm, 3);
            }
        }

        $property->setRegionCenterCityId($regionCenterCityId);
        $property->setRegionCenterDistanceKm($regionCenterDistanceKm);
    }
}
