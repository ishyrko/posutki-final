<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\PropertyPlacementScopeSettings;

interface PropertyPlacementScopeSettingsRepositoryInterface
{
    public function save(PropertyPlacementScopeSettings $settings): void;

    public function findById(int $id): ?PropertyPlacementScopeSettings;

    public function findActiveByCityId(int $cityId): ?PropertyPlacementScopeSettings;

    public function findActiveByRegionId(int $regionId): ?PropertyPlacementScopeSettings;

    /**
     * @return int[]
     */
    public function findConfiguredCityIds(string $propertyType): array;

    /**
     * @return int[]
     */
    public function findConfiguredRegionIds(string $propertyType): array;
}
