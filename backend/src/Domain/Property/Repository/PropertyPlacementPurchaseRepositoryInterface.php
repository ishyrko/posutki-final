<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Shared\ValueObject\Id;

interface PropertyPlacementPurchaseRepositoryInterface
{
    public function save(PropertyPlacementPurchase $purchase): void;

    public function findById(int $id): ?PropertyPlacementPurchase;

    /**
     * @return PropertyPlacementPurchase[]
     */
    public function findByPropertyId(int $propertyId): array;

    /**
     * @return PropertyPlacementPurchase[]
     */
    public function findByOwnerId(Id $ownerId): array;

    public function countPendingPaymentByOwnerId(Id $ownerId, ?\DateTimeImmutable $now = null): int;

    public function findActiveLevelByPropertyId(int $propertyId, ?\DateTimeImmutable $now = null): ?PropertyPlacementPurchase;

    public function countOccupiedForLevelPrice(int $levelPriceId, ?\DateTimeImmutable $now = null): int;

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
