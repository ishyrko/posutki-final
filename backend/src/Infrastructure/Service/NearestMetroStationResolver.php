<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Property\Entity\MetroStation;
use App\Domain\Property\Repository\MetroStationRepositoryInterface;
use App\Domain\Property\ValueObject\Coordinates;

final class NearestMetroStationResolver
{
    private const MAX_DISTANCE_KM = 3.0;

    public function __construct(
        private readonly MetroStationRepositoryInterface $metroStationRepository,
    ) {
    }

    /**
     * @return array{name: string, slug: string, distanceKm: float}|null
     */
    public function resolve(int $cityId, float $latitude, float $longitude): ?array
    {
        $origin = Coordinates::create($latitude, $longitude);
        $nearest = null;
        $nearestDistance = self::MAX_DISTANCE_KM;

        foreach ($this->metroStationRepository->findByCityId($cityId) as $station) {
            $distanceKm = $this->distanceToStation($origin, $station);
            if ($distanceKm === null || $distanceKm > $nearestDistance) {
                continue;
            }

            $nearestDistance = $distanceKm;
            $nearest = $station;
        }

        if (!$nearest instanceof MetroStation) {
            return null;
        }

        return [
            'name' => $nearest->getName(),
            'slug' => $nearest->getSlug(),
            'distanceKm' => round($nearestDistance, 2),
        ];
    }

    private function distanceToStation(Coordinates $origin, MetroStation $station): ?float
    {
        $latitude = $station->getLatitude();
        $longitude = $station->getLongitude();
        if ($latitude === null || $longitude === null) {
            return null;
        }

        return $origin->distanceTo(Coordinates::create($latitude, $longitude));
    }
}
