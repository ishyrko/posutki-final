<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\PropertyPlacementLevelPrice;

interface PropertyPlacementLevelPriceRepositoryInterface
{
    public function save(PropertyPlacementLevelPrice $levelPrice): void;

    public function findById(int $id): ?PropertyPlacementLevelPrice;

    /**
     * @return PropertyPlacementLevelPrice[] ordered by level
     */
    public function findActiveByCityId(int $cityId): array;

    /**
     * @return PropertyPlacementLevelPrice[] ordered by level
     */
    public function findActiveByRegionId(int $regionId): array;

    /**
     * @return PropertyPlacementLevelPrice[]
     */
    public function findAll(): array;
}
