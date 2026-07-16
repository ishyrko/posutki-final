<?php

declare(strict_types=1);

namespace App\Application\Command\Property\CreatePlacementPurchase;

use App\Application\Service\PropertyPlacementService;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyPlacementLevelPrice;
use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Enum\PlacementPurchaseKind;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\ConflictException;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;

final class CreatePlacementPurchaseHandler
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyPlacementPurchaseRepositoryInterface $purchaseRepository,
        private readonly PropertyPlacementService $placementService,
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

        $kind = $command->kind;
        if (!in_array($kind, PlacementPurchaseKind::values(), true)) {
            throw new DomainException('Неизвестный тип покупки размещения');
        }

        $purchase = $kind === PlacementPurchaseKind::Boost->value
            ? $this->createBoostPurchase($property, $userId)
            : $this->createLevelPurchase($property, $userId, $command);

        $this->purchaseRepository->save($purchase);

        return [
            'id' => $purchase->getId(),
            'propertyId' => $purchase->getPropertyId(),
            'kind' => $purchase->getKind(),
            'level' => $purchase->getLevel(),
            'durationMonths' => $purchase->getDurationMonths(),
            'priceByn' => $purchase->getPriceByn(),
            'status' => $purchase->getStatus(),
            'createdAt' => $purchase->getCreatedAt()->format('c'),
            'reservationExpiresAt' => $purchase->getReservationExpiresAt()?->format('c'),
        ];
    }

    private function createLevelPurchase(Property $property, Id $userId, CreatePlacementPurchaseCommand $command): PropertyPlacementPurchase
    {
        $level = $command->level;
        if (
            $level === null
            || $level < PropertyPlacementLevelPrice::MIN_LEVEL
            || $level > PropertyPlacementLevelPrice::MAX_LEVEL
        ) {
            throw new DomainException(sprintf(
                'Укажите VIP-уровень от %d до %d',
                PropertyPlacementLevelPrice::MIN_LEVEL,
                PropertyPlacementLevelPrice::MAX_LEVEL,
            ));
        }

        $durationMonths = $command->durationMonths;
        if ($durationMonths === null || !in_array($durationMonths, PropertyPlacementPurchase::ALLOWED_DURATIONS, true)) {
            throw new DomainException('Допустимый срок: 1, 3, 6 или 12 месяцев');
        }

        $levelPrice = $this->resolveLevelPrice($property, $level);
        if ($levelPrice === null) {
            throw new DomainException('Для этого VIP-уровня и локации тариф не задан');
        }

        if ($levelPrice->getCapacity() !== null) {
            $occupied = $this->purchaseRepository->countOccupiedForLevelPrice($levelPrice->getId() ?? 0);
            if ($occupied >= $levelPrice->getCapacity()) {
                throw new ConflictException('Нет свободных мест на этом VIP-уровне');
            }
        }

        $quote = $this->placementService->quoteLevelPurchase($property, $levelPrice, $durationMonths);

        return new PropertyPlacementPurchase(
            propertyId: $property->getId()->getValue(),
            ownerId: $userId,
            kind: PlacementPurchaseKind::Level->value,
            priceByn: $quote['priceByn'],
            source: 'self_service',
            level: $level,
            levelPriceId: $levelPrice->getId(),
            durationMonths: $durationMonths,
            basePurchaseId: $quote['anchorPurchase']?->getId(),
        );
    }

    private function createBoostPurchase(Property $property, Id $userId): PropertyPlacementPurchase
    {
        $maxLevel = $this->placementService->resolveMaxLevelForProperty($property);
        if ($property->getPlacementBaseLevel() >= $maxLevel) {
            throw new DomainException('Буст недоступен: объявление уже на максимальном VIP-уровне для этой локации');
        }

        $settings = $this->placementService->resolveScopeSettings($property);
        if ($settings === null) {
            throw new DomainException('Для этой локации цена VIP-буста не задана');
        }

        return new PropertyPlacementPurchase(
            propertyId: $property->getId()->getValue(),
            ownerId: $userId,
            kind: PlacementPurchaseKind::Boost->value,
            priceByn: $settings->getBoostPriceByn(),
            source: 'self_service',
        );
    }

    private function resolveLevelPrice(Property $property, int $level): ?PropertyPlacementLevelPrice
    {
        foreach ($this->placementService->findLevelPricesForProperty($property) as $levelPrice) {
            if ($levelPrice->getLevel() === $level) {
                return $levelPrice;
            }
        }

        return null;
    }
}
