<?php

declare(strict_types=1);

namespace App\Domain\Property\ValueObject;

final readonly class PropertyStatsFilter
{
    public function __construct(
        public ?string $propertyType = null,
        public ?int $cityId = null,
        public ?int $regionId = null,
        public ?int $ownerId = null,
    ) {
    }
}
