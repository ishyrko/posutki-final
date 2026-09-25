<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Property\Entity\City;

final class LocationDistanceResolver
{
    private const MIN_VISIBLE_KM = 0.5;

    private static ?CityNameGenitiveResolver $genitiveResolver = null;

    /**
     * @return array{
     *     nearestCity: ?array{cityName: string, citySlug: string, cityNameGenitive: ?string, distanceKm: float},
     *     regionCenter: ?array{cityName: string, citySlug: string, cityNameGenitive: ?string, distanceKm: float}
     * }
     */
    public static function resolve(
        ?int $nearestCityId,
        ?float $nearestCityDistanceKm,
        ?City $nearestCity,
        ?int $regionCenterCityId,
        ?float $regionCenterDistanceKm,
        ?City $regionCenterCity,
    ): array {
        $nearest = self::buildEntry($nearestCityId, $nearestCityDistanceKm, $nearestCity);
        $regionCenter = self::buildEntry($regionCenterCityId, $regionCenterDistanceKm, $regionCenterCity);

        if (
            $nearest !== null
            && $regionCenter !== null
            && $nearestCityId === $regionCenterCityId
        ) {
            $regionCenter = null;
        }

        return [
            'nearestCity' => $nearest,
            'regionCenter' => $regionCenter,
        ];
    }

    /**
     * @return ?array{cityName: string, citySlug: string, cityNameGenitive: ?string, distanceKm: float}
     */
    private static function buildEntry(?int $cityId, ?float $distanceKm, ?City $city): ?array
    {
        if ($cityId === null || $distanceKm === null || $city === null) {
            return null;
        }

        if ($distanceKm < self::MIN_VISIBLE_KM) {
            return null;
        }

        return [
            'cityName' => $city->getName(),
            'citySlug' => $city->getSlug(),
            'cityNameGenitive' => self::genitiveResolver()->resolve($city),
            'distanceKm' => self::roundForDisplay($distanceKm),
        ];
    }

    private static function genitiveResolver(): CityNameGenitiveResolver
    {
        return self::$genitiveResolver ??= new CityNameGenitiveResolver();
    }

    private static function roundForDisplay(float $distanceKm): float
    {
        if ($distanceKm < 10) {
            return round($distanceKm, 1);
        }

        return (float) round($distanceKm);
    }
}
