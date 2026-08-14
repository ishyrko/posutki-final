<?php

declare(strict_types=1);

namespace App\Domain\Property\Service;

interface GeocoderResponseProviderInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function resolve(
        float $latitude,
        float $longitude,
        ?int $propertyId = null,
        bool $forceRefresh = false,
    ): ?array;
}
