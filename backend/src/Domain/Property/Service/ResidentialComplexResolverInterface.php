<?php

declare(strict_types=1);

namespace App\Domain\Property\Service;

use App\Domain\Property\Entity\ResidentialComplex;

interface ResidentialComplexResolverInterface
{
    public function resolve(
        float $latitude,
        float $longitude,
        int $cityId,
        ?int $propertyId = null,
        bool $forceRefresh = false,
    ): ?ResidentialComplex;
}
