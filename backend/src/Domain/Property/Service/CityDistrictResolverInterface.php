<?php

declare(strict_types=1);

namespace App\Domain\Property\Service;

use App\Domain\Property\Entity\CityDistrict;

interface CityDistrictResolverInterface
{
    public function resolve(
        float $latitude,
        float $longitude,
        int $cityId,
        ?int $propertyId = null,
        bool $forceRefresh = false,
    ): ?CityDistrict;
}
