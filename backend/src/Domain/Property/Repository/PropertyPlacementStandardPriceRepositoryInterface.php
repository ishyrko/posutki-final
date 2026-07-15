<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\PropertyPlacementStandardPrice;

interface PropertyPlacementStandardPriceRepositoryInterface
{
    public function save(PropertyPlacementStandardPrice $price): void;

    public function findById(int $id): ?PropertyPlacementStandardPrice;

    public function findActiveByCityId(int $cityId): ?PropertyPlacementStandardPrice;
}
