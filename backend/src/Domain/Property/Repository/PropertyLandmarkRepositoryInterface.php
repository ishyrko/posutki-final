<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\PropertyLandmark;

interface PropertyLandmarkRepositoryInterface
{
    /** @return PropertyLandmark[] */
    public function findByPropertyId(int $propertyId): array;

    /** @return PropertyLandmark[] */
    public function findByPropertyIds(array $propertyIds): array;

    public function deleteByPropertyId(int $propertyId): void;

    public function countByLandmarkId(int $landmarkId): int;

    public function save(PropertyLandmark $propertyLandmark): void;
}
