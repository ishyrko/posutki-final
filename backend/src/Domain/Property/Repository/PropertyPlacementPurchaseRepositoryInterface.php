<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\PropertyPlacementPurchase;

interface PropertyPlacementPurchaseRepositoryInterface
{
    public function save(PropertyPlacementPurchase $purchase): void;

    public function findById(int $id): ?PropertyPlacementPurchase;

    /**
     * @return PropertyPlacementPurchase[]
     */
    public function findByPropertyId(int $propertyId): array;

    public function findActiveSpecialByPropertyId(int $propertyId, ?\DateTimeImmutable $now = null): ?PropertyPlacementPurchase;

    public function findActiveStandardByPropertyId(int $propertyId, ?\DateTimeImmutable $now = null): ?PropertyPlacementPurchase;

    public function countOccupiedForSlot(int $slotId, ?\DateTimeImmutable $now = null): int;

    /**
     * @return PropertyPlacementPurchase[]
     */
    public function findExpiredActive(?\DateTimeImmutable $now = null): array;

    /**
     * @return PropertyPlacementPurchase[]
     */
    public function findExpiredReservations(?\DateTimeImmutable $now = null): array;

    /**
     * @return int[] property ids that may need placement recompute
     */
    public function findPropertyIdsNeedingRecompute(?\DateTimeImmutable $now = null): array;
}
