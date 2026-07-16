<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\PropertyPlacementSlot;

interface PropertyPlacementSlotRepositoryInterface
{
    public function save(PropertyPlacementSlot $slot): void;

    public function findById(int $id): ?PropertyPlacementSlot;

    /**
     * @return PropertyPlacementSlot[]
     */
    public function findActiveByCityId(int $cityId): array;

    /**
     * @return PropertyPlacementSlot[]
     */
    public function findActiveByRegionId(int $regionId): array;

    /**
     * @return PropertyPlacementSlot[]
     */
    public function findAll(): array;
}
