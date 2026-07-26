<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetAdminPropertyStatsOverview;

final class GetAdminPropertyStatsOverviewQuery
{
    public function __construct(
        public readonly int $period,
        public readonly ?string $propertyType,
        public readonly ?int $cityId,
        public readonly ?int $regionId = null,
    ) {
    }
}
