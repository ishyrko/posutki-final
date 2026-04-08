<?php

declare(strict_types=1);

namespace App\Application\Query\Property\SearchProperties;

final class SearchPropertiesQuery
{
    public function __construct(
        public readonly ?string $type = null,
        public readonly ?array $types = null,
        public readonly ?string $dealType = null,
        public readonly ?string $regionSlug = null,
        public readonly ?string $citySlug = null,
        public readonly ?int $cityId = null,
        public readonly ?int $minPrice = null,
        public readonly ?int $maxPrice = null,
        public readonly string $priceType = 'total',  // 'total' | 'perMeter'
        public readonly ?string $currency = null,      // 'BYN' | 'USD' | 'EUR'
        public readonly ?float $minArea = null,
        public readonly ?float $maxArea = null,
        public readonly ?int $rooms = null,
        public readonly ?int $metroStationId = null,
        public readonly bool $nearMetro = false,
        public readonly string $sortBy = 'createdAt',
        public readonly string $sortOrder = 'DESC',
        public readonly int $page = 1,
        public readonly int $limit = 20,
    ) {
    }
}