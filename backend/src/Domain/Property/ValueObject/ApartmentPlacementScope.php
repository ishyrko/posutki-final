<?php

declare(strict_types=1);

namespace App\Domain\Property\ValueObject;

/**
 * VIP tariff and catalog position scope for apartment listings.
 */
final class ApartmentPlacementScope
{
    /**
     * @param list<string> $excludeCitySlugs
     */
    public function __construct(
        public readonly int $tariffCityId,
        public readonly string $locationLabel,
        public readonly ?int $catalogCityId,
        public readonly ?int $catalogRegionId,
        public readonly array $excludeCitySlugs = [],
    ) {
    }

    public static function forCity(int $cityId, string $locationLabel): self
    {
        return new self(
            tariffCityId: $cityId,
            locationLabel: $locationLabel,
            catalogCityId: $cityId,
            catalogRegionId: null,
        );
    }

    /**
     * @param list<string> $excludeCitySlugs
     */
    public static function forRegion(
        int $tariffCityId,
        string $locationLabel,
        int $catalogRegionId,
        array $excludeCitySlugs,
    ): self {
        return new self(
            tariffCityId: $tariffCityId,
            locationLabel: $locationLabel,
            catalogCityId: null,
            catalogRegionId: $catalogRegionId,
            excludeCitySlugs: $excludeCitySlugs,
        );
    }
}
