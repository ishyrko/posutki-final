<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'property_metro_stations')]
#[ORM\Index(name: 'idx_property_metro_property', columns: ['property_id'])]
#[ORM\Index(name: 'idx_property_metro_station', columns: ['metro_station_id'])]
class PropertyMetroStation
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'property_id')]
    private int $propertyId;

    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'metro_station_id')]
    private int $metroStationId;

    #[ORM\Column(type: 'float', name: 'distance_km')]
    private float $distanceKm;

    public function __construct(int $propertyId, int $metroStationId, float $distanceKm)
    {
        $this->propertyId = $propertyId;
        $this->metroStationId = $metroStationId;
        $this->distanceKm = $distanceKm;
    }

    public function getPropertyId(): int
    {
        return $this->propertyId;
    }

    public function getMetroStationId(): int
    {
        return $this->metroStationId;
    }

    public function getDistanceKm(): float
    {
        return $this->distanceKm;
    }
}
