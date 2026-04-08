<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyMetroStation;
use App\Domain\Property\Repository\MetroStationRepositoryInterface;
use App\Domain\Property\Repository\PropertyMetroStationRepositoryInterface;
use App\Domain\Property\ValueObject\Coordinates;

final readonly class MetroProximityCalculator
{
    private const MAX_DISTANCE_KM = 1.0;

    public function __construct(
        private MetroStationRepositoryInterface $metroStationRepository,
        private PropertyMetroStationRepositoryInterface $propertyMetroStationRepository,
    ) {
    }

    public function syncForProperty(Property $property): void
    {
        $propertyId = $property->getId()->getValue();
        $propertyCoordinates = $property->getCoordinates();

        $this->propertyMetroStationRepository->deleteByPropertyId($propertyId);

        $nearbyCount = 0;
        foreach ($this->metroStationRepository->findAll() as $station) {
            $latitude = $station->getLatitude();
            $longitude = $station->getLongitude();

            if ($latitude === null || $longitude === null) {
                continue;
            }

            $stationCoordinates = Coordinates::create($latitude, $longitude);
            $distanceKm = $propertyCoordinates->distanceTo($stationCoordinates);

            if ($distanceKm > self::MAX_DISTANCE_KM) {
                continue;
            }

            $this->propertyMetroStationRepository->save(
                new PropertyMetroStation(
                    propertyId: $propertyId,
                    metroStationId: $station->getId(),
                    distanceKm: round($distanceKm, 3),
                )
            );

            $nearbyCount++;
        }

        $property->setNearMetro($nearbyCount > 0);
    }
}
