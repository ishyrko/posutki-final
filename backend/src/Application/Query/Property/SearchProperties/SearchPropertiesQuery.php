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
        public readonly ?string $currency = null,      // 'BYN' | 'USD' | 'RUB'
        public readonly ?float $minArea = null,
        public readonly ?float $maxArea = null,
        /** @var list<int>|null ints 1–4; 4 means four or more rooms; multiple values are OR */
        public readonly ?array $rooms = null,
        public readonly ?int $metroStationId = null,
        public readonly bool $nearMetro = false,
        /** Минимальная вместимость: объявления с maxDailyGuests >= guests */
        public readonly ?int $guests = null,
        public readonly string $sortBy = 'createdAt',
        public readonly string $sortOrder = 'DESC',
        public readonly int $page = 1,
        public readonly int $limit = 20,
    ) {
    }
}