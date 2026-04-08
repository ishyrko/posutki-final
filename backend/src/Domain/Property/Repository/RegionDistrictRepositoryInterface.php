<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\RegionDistrict;

interface RegionDistrictRepositoryInterface
{
    public function findById(int $id): ?RegionDistrict;

    /** @return RegionDistrict[] */
    public function findByRegionId(int $regionId): array;
}
