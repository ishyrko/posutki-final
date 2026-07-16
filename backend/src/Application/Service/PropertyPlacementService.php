<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Entity\PropertyPlacementSlot;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementSlotRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;

final class PropertyPlacementService
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyPlacementPurchaseRepositoryInterface $purchaseRepository,
        private readonly PropertyPlacementSlotRepositoryInterface $slotRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function recomputeForProperty(Property $property, ?\DateTimeImmutable $now = null): void
    {
        $now ??= new \DateTimeImmutable();
        $propertyId = $property->getId()->getValue();

        $special = $this->purchaseRepository->findActiveSpecialByPropertyId($propertyId, $now);
        $standard = $this->purchaseRepository->findActiveStandardByPropertyId($propertyId, $now);

        $activeSpecial = null;
        if ($special !== null) {
            $slot = $special->getSlotId() !== null
                ? $this->slotRepository->findById($special->getSlotId())
                : null;
            $activeSpecial = [
                'slotRank' => $slot?->getRankFrom() ?? 9999,
                'expiresAt' => $special->getExpiresAt() ?? $now,
            ];
        }

        $activeStandard = null;
        if ($standard !== null && $standard->getExpiresAt() !== null) {
            $activeStandard = [
                'expiresAt' => $standard->getExpiresAt(),
            ];
        }

        $property->recomputePlacement($activeSpecial, $activeStandard, $now);
        $this->propertyRepository->save($property);
    }

    public function recomputeForPropertyId(int $propertyId, ?\DateTimeImmutable $now = null): void
    {
        $property = $this->propertyRepository->findById(Id::fromInt($propertyId));
        if ($property === null) {
            return;
        }

        $this->recomputeForProperty($property, $now);
    }

    public function activatePurchase(
        PropertyPlacementPurchase $purchase,
        Property $property,
        ?Id $adminId = null,
        ?\DateTimeImmutable $now = null,
    ): void {
        $now ??= new \DateTimeImmutable();

        if ($purchase->getSlotId() !== null) {
            $slot = $this->slotRepository->findById($purchase->getSlotId());
            if ($slot === null || !$slot->isActive()) {
                throw new \App\Domain\Shared\Exception\DomainException('Слот размещения не найден или неактивен');
            }
            $occupied = $this->purchaseRepository->countOccupiedForSlot($slot->getId() ?? 0, $now);
            // Current purchase still counts as pending reservation — exclude it for capacity check after activation intent
            // When activating, the purchase itself is still pending, so occupied includes it. Capacity must allow it.
            if ($occupied > $slot->getCapacity()) {
                throw new \App\Domain\Shared\Exception\DomainException('Нет свободных мест в выбранном диапазоне позиций');
            }
            if ($occupied === $slot->getCapacity()) {
                // OK — this purchase is the last reserved seat
            }
        }

        $purchase->activate($adminId, $now);
        $this->purchaseRepository->save($purchase);
        $this->recomputeForProperty($property, $now);
    }

    public function getSlotOccupancy(PropertyPlacementSlot $slot, ?\DateTimeImmutable $now = null): int
    {
        $id = $slot->getId();
        if ($id === null) {
            return 0;
        }

        return $this->purchaseRepository->countOccupiedForSlot($id, $now);
    }

    /**
     * One free standard trial per account: true if the owner has not used it yet.
     */
    public function shouldGrantFreeTrial(Property $property): bool
    {
        $user = $this->userRepository->findById($property->getOwnerId());
        if ($user === null) {
            return false;
        }

        return !$user->hasUsedFreePlacementTrial();
    }

    /**
     * Mark the owner account as having consumed the one-time free standard month.
     * Call only after the trial was actually applied to a listing.
     */
    public function markFreePlacementTrialUsed(Property $property): void
    {
        $user = $this->userRepository->findById($property->getOwnerId());
        if ($user === null || $user->hasUsedFreePlacementTrial()) {
            return;
        }

        $user->markFreePlacementTrialUsed();
        $this->userRepository->save($user);
    }
}
