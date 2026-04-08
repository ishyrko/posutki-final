<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\PropertyMetroStation;

interface PropertyMetroStationRepositoryInterface
{
    /** @return PropertyMetroStation[] */
    public function findByPropertyId(int $propertyId): array;

    /** @return PropertyMetroStation[] */
    public function findByPropertyIds(array $propertyIds): array;

    public function deleteByPropertyId(int $propertyId): void;

    public function save(PropertyMetroStation $propertyMetroStation): void;
}
