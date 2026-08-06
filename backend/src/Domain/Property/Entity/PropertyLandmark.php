<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'property_landmarks')]
#[ORM\Index(name: 'idx_property_landmark_property', columns: ['property_id'])]
#[ORM\Index(name: 'idx_property_landmark_landmark', columns: ['landmark_id'])]
class PropertyLandmark
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'property_id')]
    private int $propertyId;

    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'landmark_id')]
    private int $landmarkId;

    #[ORM\Column(type: 'float', name: 'distance_km')]
    private float $distanceKm;

    public function __construct(int $propertyId, int $landmarkId, float $distanceKm)
    {
        $this->propertyId = $propertyId;
        $this->landmarkId = $landmarkId;
        $this->distanceKm = $distanceKm;
    }

    public function getPropertyId(): int
    {
        return $this->propertyId;
    }

    public function getLandmarkId(): int
    {
        return $this->landmarkId;
    }

    public function getDistanceKm(): float
    {
        return $this->distanceKm;
    }
}
