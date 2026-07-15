<?php

declare(strict_types=1);

namespace App\Application\Command\Property\CreatePlacementPurchase;

use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Enum\PlacementPurchaseType;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementSlotRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementStandardPriceRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\ConflictException;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;

final class CreatePlacementPurchaseHandler
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyPlacementPurchaseRepositoryInterface $purchaseRepository,
        private readonly PropertyPlacementSlotRepositoryInterface $slotRepository,
        private readonly PropertyPlacementStandardPriceRepositoryInterface $standardPriceRepository,
    ) {
    }

    public function __invoke(CreatePlacementPurchaseCommand $command): array
    {
        $propertyId = Id::fromString($command->propertyId);
        $userId = Id::fromString($command->userId);

        $property = $this->propertyRepository->findById($propertyId);
        if ($property === null) {
            throw new DomainException('Объявление не найдено');
        }
        if (!$property->getOwnerId()->equals($userId)) {
            throw new DomainException('Нет прав на это объявление');
        }
        if ($property->getStatus() !== 'published') {
            throw new DomainException('Размещение можно купить только для опубликованного объявления');
        }

        $type = $command->type;
        if (!in_array($type, PlacementPurchaseType::values(), true)) {
            throw new DomainException('Неизвестный тип размещения');
        }

        $slotId = $command->slotId;
        $pricePerMonth = 0;

        if ($type === PlacementPurchaseType::Special->value) {
            if ($slotId === null) {
                throw new DomainException('Выберите диапазон позиций');
            }
            $slot = $this->slotRepository->findById($slotId);
            if ($slot === null || !$slot->isActive()) {
                throw new DomainException('Диапазон позиций не найден');
            }
            if ($slot->getCityId() !== $property->getCityId()) {
                throw new DomainException('Диапазон позиций не относится к городу объявления');
            }
            $occupied = $this->purchaseRepository->countOccupiedForSlot($slotId);
            if ($occupied >= $slot->getCapacity()) {
                throw new ConflictException('Нет свободных мест в выбранном диапазоне позиций');
            }
            $pricePerMonth = $slot->getPriceBynPerMonth();
        } else {
            $standard = $this->standardPriceRepository->findActiveByCityId($property->getCityId());
            if ($standard === null) {
                throw new DomainException('Для этого города ещё не задана цена стандартного размещения');
            }
            $pricePerMonth = $standard->getPriceBynPerMonth();
            $slotId = null;
        }

        $totalPrice = $pricePerMonth * $command->durationMonths;

        $purchase = new PropertyPlacementPurchase(
            propertyId: $property->getId()->getValue(),
            ownerId: $userId,
            type: $type,
            durationMonths: $command->durationMonths,
            priceByn: $totalPrice,
            source: 'self_service',
            slotId: $slotId,
        );

        $this->purchaseRepository->save($purchase);

        return [
            'id' => $purchase->getId(),
            'propertyId' => $purchase->getPropertyId(),
            'type' => $purchase->getType(),
            'slotId' => $purchase->getSlotId(),
            'durationMonths' => $purchase->getDurationMonths(),
            'priceByn' => $purchase->getPriceByn(),
            'status' => $purchase->getStatus(),
            'createdAt' => $purchase->getCreatedAt()->format('c'),
            'reservationExpiresAt' => $purchase->getReservationExpiresAt()?->format('c'),
        ];
    }
}
