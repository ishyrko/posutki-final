<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Property\Entity\Landmark;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyLandmark;
use App\Domain\Property\Repository\LandmarkRepositoryInterface;
use App\Domain\Property\Repository\PropertyLandmarkRepositoryInterface;
use App\Domain\Property\ValueObject\Coordinates;

final readonly class LandmarkProximityCalculator
{
    public function __construct(
        private LandmarkRepositoryInterface $landmarkRepository,
        private PropertyLandmarkRepositoryInterface $propertyLandmarkRepository,
    ) {
    }

    public function syncForProperty(Property $property): void
    {
        $propertyId = $property->getId()->getValue();
        $propertyCoordinates = $property->getCoordinates();

        $this->propertyLandmarkRepository->deleteByPropertyId($propertyId);

        if (!$property->hasStreet()) {
            return;
        }

        foreach ($this->landmarkRepository->findByCityId($property->getCityId()) as $landmark) {
            if (!$landmark->hasCoordinates()) {
                continue;
            }

            $landmarkCoordinates = Coordinates::create(
                $landmark->getLatitude(),
                $landmark->getLongitude(),
            );
            $distanceKm = $propertyCoordinates->distanceTo($landmarkCoordinates);

            if ($distanceKm > Landmark::PROXIMITY_RADIUS_KM) {
                continue;
            }

            $this->propertyLandmarkRepository->save(
                new PropertyLandmark(
                    propertyId: $propertyId,
                    landmarkId: $landmark->getId(),
                    distanceKm: round($distanceKm, 3),
                )
            );
        }
    }
}
