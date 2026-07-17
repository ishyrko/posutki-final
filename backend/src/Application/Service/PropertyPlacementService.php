<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyPlacementLevelPrice;
use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Entity\PropertyPlacementScopeSettings;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementLevelPriceRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementScopeSettingsRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;

final class PropertyPlacementService
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyPlacementPurchaseRepositoryInterface $purchaseRepository,
        private readonly PropertyPlacementLevelPriceRepositoryInterface $levelPriceRepository,
        private readonly PropertyPlacementScopeSettingsRepositoryInterface $scopeSettingsRepository,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function recomputeForProperty(Property $property, ?\DateTimeImmutable $now = null): void
    {
        $now ??= new \DateTimeImmutable();
        $propertyId = $property->getId()->getValue();

        $activeLevelPurchase = $this->purchaseRepository->findActiveLevelByPropertyId($propertyId, $now);
        $activeLevel = null;
        if ($activeLevelPurchase !== null && $activeLevelPurchase->getLevel() !== null && $activeLevelPurchase->getExpiresAt() !== null) {
            $activeLevel = [
                'level' => $activeLevelPurchase->getLevel(),
                'expiresAt' => $activeLevelPurchase->getExpiresAt(),
            ];
        }

        $maxLevel = $this->resolveMaxLevelForProperty($property);
        $property->recomputePlacement($activeLevel, $now, $maxLevel);
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

        if ($purchase->isBoost()) {
            $maxLevel = $this->resolveMaxLevelForProperty($property);
            if ($property->getPlacementBaseLevel() >= $maxLevel) {
                throw new DomainException('Буст недоступен: объявление уже на максимальном VIP-уровне для этой локации');
            }

            $purchase->activate($adminId, $now);
            $this->purchaseRepository->save($purchase);
            $property->extendPlacementBoost($now);
            $this->recomputeForProperty($property, $now);

            return;
        }

        if ($purchase->getLevelPriceId() !== null) {
            $levelPrice = $this->levelPriceRepository->findById($purchase->getLevelPriceId());
            if ($levelPrice === null || !$levelPrice->isActive()) {
                throw new DomainException('Тариф VIP-уровня не найден или неактивен');
            }
            if ($levelPrice->getCapacity() !== null) {
                $occupied = $this->purchaseRepository->countOccupiedForLevelPrice($levelPrice->getId() ?? 0, $now);
                if ($occupied > $levelPrice->getCapacity()) {
                    throw new DomainException('Нет свободных мест на этом VIP-уровне');
                }
            }
        }

        $expiresAtOverride = $this->resolveActivationExpiresAt($purchase, $now);

        $purchase->activate($adminId, $now, $expiresAtOverride);
        $this->purchaseRepository->save($purchase);
        $this->recomputeForProperty($property, $now);
    }

    /**
     * VIP-boost (24h) price: twice the daily tariff gap between the current level and the next one.
     * Daily rate is monthly price / 30. Level 0 (free) is treated as 0 BYN/month.
     */
    public function quoteBoostPurchase(Property $property): int
    {
        $currentLevel = $property->getPlacementBaseLevel();
        $maxLevel = $this->resolveMaxLevelForProperty($property);

        if ($currentLevel >= $maxLevel) {
            throw new DomainException('Буст недоступен: объявление уже на максимальном VIP-уровне для этой локации');
        }

        $nextLevel = $currentLevel + 1;
        $pricesByLevel = [];
        foreach ($this->findLevelPricesForProperty($property) as $levelPrice) {
            $pricesByLevel[$levelPrice->getLevel()] = $levelPrice->getPriceBynPerMonth();
        }

        if (!isset($pricesByLevel[$nextLevel])) {
            throw new DomainException('Для следующего VIP-уровня тариф не задан');
        }

        $currentPricePerMonth = 0;
        if ($currentLevel > 0) {
            if (!isset($pricesByLevel[$currentLevel])) {
                throw new DomainException('Для текущего VIP-уровня тариф не задан');
            }
            $currentPricePerMonth = $pricesByLevel[$currentLevel];
        }

        $dailyDiff = ($pricesByLevel[$nextLevel] - $currentPricePerMonth) / 30;

        return max(0, (int) ceil($dailyDiff * 2));
    }

    /**
     * @return array{
     *     mode: 'new'|'renewal'|'upgrade',
     *     priceByn: int,
     *     anchorPurchase: ?PropertyPlacementPurchase,
     *     expiresAtPreview: ?\DateTimeImmutable
     * }
     */
    public function quoteLevelPurchase(
        Property $property,
        PropertyPlacementLevelPrice $levelPrice,
        int $durationMonths,
        ?\DateTimeImmutable $now = null,
    ): array {
        $now ??= new \DateTimeImmutable();
        $propertyId = $property->getId()->getValue();
        $targetLevel = $levelPrice->getLevel();

        $anchor = $this->purchaseRepository->findActiveLevelByPropertyId($propertyId, $now);

        if ($anchor === null) {
            return [
                'mode' => 'new',
                'priceByn' => $levelPrice->getPriceBynPerMonth() * $durationMonths,
                'anchorPurchase' => null,
                'expiresAtPreview' => $now->modify('+' . $durationMonths . ' months'),
            ];
        }

        $anchorLevel = $anchor->getLevel();
        if ($anchorLevel === null) {
            throw new DomainException('Активная заявка не содержит VIP-уровень');
        }

        if ($targetLevel < $anchorLevel) {
            throw new DomainException('Понижение VIP-уровня недоступно, пока действует более высокий уровень');
        }

        if ($targetLevel === $anchorLevel) {
            $anchorExpiresAt = $anchor->getExpiresAt();
            if ($anchorExpiresAt === null) {
                throw new DomainException('У активной заявки не задан срок действия');
            }

            $cap = $now->modify('+12 months');
            $candidate = $anchorExpiresAt->modify('+' . $durationMonths . ' months');

            if ($candidate > $cap) {
                $availableMonths = $this->monthsBetween($anchorExpiresAt, $cap);
                throw new DomainException(sprintf(
                    'Продление недоступно на %d мес.: максимальный срок подписки — 12 месяцев от сегодня. Доступно ещё %d мес.',
                    $durationMonths,
                    $availableMonths,
                ));
            }

            return [
                'mode' => 'renewal',
                'priceByn' => $levelPrice->getPriceBynPerMonth() * $durationMonths,
                'anchorPurchase' => $anchor,
                'expiresAtPreview' => $candidate,
            ];
        }

        $anchorLevelPriceId = $anchor->getLevelPriceId();
        if ($anchorLevelPriceId === null) {
            throw new DomainException('У активной заявки не задан тариф уровня');
        }

        $oldLevelPrice = $this->levelPriceRepository->findById($anchorLevelPriceId);
        if ($oldLevelPrice === null) {
            throw new DomainException('Тариф текущего VIP-уровня не найден');
        }

        $anchorExpiresAt = $anchor->getExpiresAt();
        if ($anchorExpiresAt === null) {
            throw new DomainException('У активной заявки не задан срок действия');
        }

        $remainingDays = max(0.0, ($anchorExpiresAt->getTimestamp() - $now->getTimestamp()) / 86400);
        $diffPerMonth = $levelPrice->getPriceBynPerMonth() - $oldLevelPrice->getPriceBynPerMonth();
        $priceByn = max(0, (int) round($diffPerMonth * $remainingDays / 30));

        return [
            'mode' => 'upgrade',
            'priceByn' => $priceByn,
            'anchorPurchase' => $anchor,
            'expiresAtPreview' => $anchorExpiresAt,
        ];
    }

    private function resolveActivationExpiresAt(
        PropertyPlacementPurchase $purchase,
        \DateTimeImmutable $now,
    ): ?\DateTimeImmutable {
        $basePurchaseId = $purchase->getBasePurchaseId();
        if ($basePurchaseId === null || $purchase->isBoost()) {
            return null;
        }

        $anchor = $this->purchaseRepository->findById($basePurchaseId);
        if ($anchor === null || !$anchor->isActive()) {
            return null;
        }

        $anchorExpiresAt = $anchor->getExpiresAt();
        if ($anchorExpiresAt === null || $anchorExpiresAt <= $now) {
            return null;
        }

        $expiresAtOverride = null;
        if ($purchase->getLevel() === $anchor->getLevel()) {
            $durationMonths = $purchase->getDurationMonths() ?? 0;
            $candidate = $anchorExpiresAt->modify('+' . $durationMonths . ' months');
            $cap = $now->modify('+12 months');
            $expiresAtOverride = $candidate > $cap ? $cap : $candidate;
        } else {
            $expiresAtOverride = $anchorExpiresAt;
        }

        $anchor->supersede();
        $this->purchaseRepository->save($anchor);

        return $expiresAtOverride;
    }

    private function monthsBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        if ($to <= $from) {
            return 0;
        }

        $diff = $from->diff($to);

        return $diff->y * 12 + $diff->m;
    }

    public function getLevelPriceOccupancy(PropertyPlacementLevelPrice $levelPrice, ?\DateTimeImmutable $now = null): int
    {
        $id = $levelPrice->getId();
        if ($id === null) {
            return 0;
        }

        return $this->purchaseRepository->countOccupiedForLevelPrice($id, $now);
    }

    /**
     * Resolve the (propertyType, cityId|regionId) VIP scope settings for a property,
     * or null if the location has no explicit configuration yet.
     */
    public function resolveScopeSettings(Property $property): ?PropertyPlacementScopeSettings
    {
        if ($property->getType() === PropertyType::House->value) {
            $regionId = $this->resolveRegionId($property);
            if ($regionId === null) {
                return null;
            }

            return $this->scopeSettingsRepository->findActiveByRegionId($regionId);
        }

        return $this->scopeSettingsRepository->findActiveByCityId($property->getCityId());
    }

    /**
     * The highest VIP level configurable for the property's city (apartments) or
     * region (houses); defaults to the global maximum when not explicitly configured.
     */
    public function resolveMaxLevelForProperty(Property $property): int
    {
        return $this->resolveScopeSettings($property)?->getMaxLevel() ?? PropertyPlacementScopeSettings::DEFAULT_MAX_LEVEL;
    }

    public function resolveRegionId(Property $property): ?int
    {
        $city = $this->cityRepository->findById($property->getCityId());

        return $city?->getRegionDistrict()?->getRegion()->getId();
    }

    /**
     * @return PropertyPlacementLevelPrice[] ordered by level, for the property's scope
     */
    public function findLevelPricesForProperty(Property $property): array
    {
        if ($property->getType() === PropertyType::House->value) {
            $regionId = $this->resolveRegionId($property);

            return $regionId !== null ? $this->levelPriceRepository->findActiveByRegionId($regionId) : [];
        }

        return $this->levelPriceRepository->findActiveByCityId($property->getCityId());
    }

    /**
     * One free VIP 1 trial per account: true if the owner has not used it yet.
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
     * Mark the owner account as having consumed the one-time free VIP 1 month.
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
