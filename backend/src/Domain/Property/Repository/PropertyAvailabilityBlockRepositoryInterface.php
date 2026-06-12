<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\PropertyAvailabilityBlock;
use App\Domain\Shared\ValueObject\Id;

interface PropertyAvailabilityBlockRepositoryInterface
{
    public function save(PropertyAvailabilityBlock $block): void;

    public function delete(PropertyAvailabilityBlock $block): void;

    public function findById(Id $id): ?PropertyAvailabilityBlock;

    /**
     * @return PropertyAvailabilityBlock[]
     */
    public function findByPropertyId(Id $propertyId): array;
}
