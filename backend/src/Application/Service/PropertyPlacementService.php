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
use App\Domain\Property\Service\ApartmentPlacementScopeResolver;
use App\Domain\Property\ValueObject\ApartmentPlacementScope;
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
        private readonly ApartmentPlacementScopeResolver $apartmentPlacementScopeResolver,
    ) {
    }

    public function recomputeForProperty(Property $property, ?\DateTimeImmutable $now = null): void
    {
        $now ??= new \DateTimeImmutable();
        $maxLevel = $this->resolveMaxLevelForProperty($property);
        // Property row is the source of truth for level / expiry / boost.
        $property->recomputePlacement($now, $maxLevel);
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
            // For boost purchases base VIP must stay as-is (it already comes from property table),
            // only effective level changes due to the boost expiry.
            $property->recomputePlacementBoostOnly($now, $maxLevel);
            $this->propertyRepository->save($property);

            return;
        }

        if ($purchase->getLevelPriceId() !== null) {
            $levelPrice = $this->levelPriceRepository->findById($purchase->getLevelPriceId());
            if ($levelPrice === null || !$levelPrice->isActive()) {
                throw new DomainException('Тариф VIP-уровня не найден или неактивен');
            }
            if ($levelPrice->getCapacity() !== null) {
                $occupied = $this->getLevelPriceOccupancy(
                    $levelPrice,
                    $now,
                    $property->getId()->getValue(),
                );
                if ($occupied >= $levelPrice->getCapacity()) {
                    throw new DomainException('Нет свободных мест на этом VIP-уровне');
                }
            }
        }

        $expiresAtOverride = $this->resolveActivationExpiresAt($purchase, $property, $now);

        $purchase->activate($adminId, $now, $expiresAtOverride);
        $this->purchaseRepository->save($purchase);

        $expiresAt = $purchase->getExpiresAt();
        $level = $purchase->getLevel();
        if ($level === null || $expiresAt === null) {
            throw new DomainException('Активированная заявка не содержит VIP-уровень или срок');
        }

        $maxLevel = $this->resolveMaxLevelForProperty($property);
        $property->applyLevelPlacement($level, $expiresAt, $now, $maxLevel);
        $this->propertyRepository->save($property);
    }

    /**
     * VIP-boost (24h) price: three times the daily tariff gap between the current level and the next one.
     * Daily rate is monthly price / 30. Level 0 (free) is treated as 0 BYN/month.
     * Never below {@see PropertyPlacementPurchase::MIN_BOOST_PRICE_BYN}.
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

        return max(
            PropertyPlacementPurchase::MIN_BOOST_PRICE_BYN,
            (int) ceil($dailyDiff * 3),
        );
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

        // Level and expiry are always read from the property row.
        $currentLevel = $property->getPlacementBaseLevel();
        $currentExpiresAt = $property->getPlacementLevelExpiresAt();
        if ($currentExpiresAt === null || $currentExpiresAt <= $now || $currentLevel <= 0) {
            $currentLevel = 0;
            $currentExpiresAt = null;
        }

        // Optional link to an active purchase for superseding / basePurchaseId only.
        $anchor = $currentLevel > 0
            ? $this->purchaseRepository->findActiveLevelByPropertyId($propertyId, $now)
            : null;

        if ($currentLevel <= 0 || $currentExpiresAt === null) {
            return [
                'mode' => 'new',
                'priceByn' => PropertyPlacementPurchase::priceForDuration(
                    $levelPrice->getPriceBynPerMonth(),
                    $durationMonths,
                ),
                'anchorPurchase' => null,
                'expiresAtPreview' => $now->modify('+' . $durationMonths . ' months'),
            ];
        }

        if ($targetLevel < $currentLevel) {
            throw new DomainException('Понижение VIP-уровня недоступно, пока действует более высокий уровень');
        }

        if ($targetLevel === $currentLevel) {
            $cap = $now->modify('+' . PropertyPlacementPurchase::MAX_HORIZON_MONTHS . ' months');
            $candidate = $currentExpiresAt->modify('+' . $durationMonths . ' months');
            $shortRemaining = $currentExpiresAt < $now->modify('+1 month');

            if ($candidate > $cap && !$shortRemaining) {
                $availableMonths = $this->monthsBetween($currentExpiresAt, $cap);
                throw new DomainException(sprintf(
                    'Продление недоступно на %d мес.: максимальный срок подписки — %d месяцев от сегодня. Доступно ещё %d мес.',
                    $durationMonths,
                    PropertyPlacementPurchase::MAX_HORIZON_MONTHS,
                    $availableMonths,
                ));
            }

            return [
                'mode' => 'renewal',
                'priceByn' => PropertyPlacementPurchase::priceForDuration(
                    $levelPrice->getPriceBynPerMonth(),
                    $durationMonths,
                ),
                'anchorPurchase' => $anchor,
                'expiresAtPreview' => $candidate,
            ];
        }

        $remainingDays = max(0.0, ($currentExpiresAt->getTimestamp() - $now->getTimestamp()) / 86400);
        if ($remainingDays < PropertyPlacementPurchase::MIN_UPGRADE_REMAINING_DAYS) {
            throw new DomainException(sprintf(
                'Апгрейд недоступен: до окончания текущего VIP осталось менее %d дней. Можно продлить текущий уровень.',
                PropertyPlacementPurchase::MIN_UPGRADE_REMAINING_DAYS,
            ));
        }

        $oldLevelPrice = null;
        foreach ($this->findLevelPricesForProperty($property) as $candidate) {
            if ($candidate->getLevel() === $currentLevel) {
                $oldLevelPrice = $candidate;
                break;
            }
        }
        if ($oldLevelPrice === null) {
            throw new DomainException('Тариф текущего VIP-уровня не найден');
        }

        $diffPerMonth = $levelPrice->getPriceBynPerMonth() - $oldLevelPrice->getPriceBynPerMonth();
        $priceByn = max(0, (int) round($diffPerMonth * $remainingDays / 30));

        return [
            'mode' => 'upgrade',
            'priceByn' => $priceByn,
            'anchorPurchase' => $anchor,
            'expiresAtPreview' => $currentExpiresAt,
        ];
    }

    private function resolveActivationExpiresAt(
        PropertyPlacementPurchase $purchase,
        Property $property,
        \DateTimeImmutable $now,
    ): ?\DateTimeImmutable {
        if ($purchase->isBoost()) {
            return null;
        }

        $currentLevel = $property->getPlacementBaseLevel();
        $currentExpiresAt = $property->getPlacementLevelExpiresAt();
        $hasActiveVip = $currentLevel > 0
            && $currentExpiresAt !== null
            && $currentExpiresAt > $now;

        $expiresAtOverride = null;
        if ($hasActiveVip) {
            if ($purchase->getLevel() === $currentLevel) {
                $durationMonths = $purchase->getDurationMonths() ?? 0;
                $candidate = $currentExpiresAt->modify('+' . $durationMonths . ' months');
                $cap = $now->modify('+' . PropertyPlacementPurchase::MAX_HORIZON_MONTHS . ' months');
                $shortRemaining = $currentExpiresAt < $now->modify('+1 month');
                $expiresAtOverride = (!$shortRemaining && $candidate > $cap) ? $cap : $candidate;
            } elseif ($purchase->getLevel() !== null && $purchase->getLevel() > $currentLevel) {
                $expiresAtOverride = $currentExpiresAt;
            }
        }

        $basePurchaseId = $purchase->getBasePurchaseId();
        if ($basePurchaseId !== null) {
            $anchor = $this->purchaseRepository->findById($basePurchaseId);
            if ($anchor !== null && $anchor->isActive()) {
                $anchor->supersede();
                $this->purchaseRepository->save($anchor);
            }
        }

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

    public function getLevelPriceOccupancy(
        PropertyPlacementLevelPrice $levelPrice,
        ?\DateTimeImmutable $now = null,
        ?int $excludePropertyId = null,
        ?ApartmentPlacementScope $apartmentScope = null,
    ): int {
        if ($levelPrice->getPropertyType() === PropertyType::House->value) {
            return $this->propertyRepository->countOccupiedAtBaseLevel(
                propertyType: $levelPrice->getPropertyType(),
                level: $levelPrice->getLevel(),
                cityId: null,
                regionId: $levelPrice->getRegionId(),
                now: $now,
                excludePropertyId: $excludePropertyId,
            );
        }

        $scope = $apartmentScope;
        if ($scope === null && $levelPrice->getCityId() !== null) {
            $scope = $this->apartmentPlacementScopeResolver->resolveForCityId($levelPrice->getCityId());
        }

        return $this->propertyRepository->countOccupiedAtBaseLevel(
            propertyType: $levelPrice->getPropertyType(),
            level: $levelPrice->getLevel(),
            cityId: $scope?->catalogCityId ?? $levelPrice->getCityId(),
            regionId: $scope?->catalogRegionId ?? $levelPrice->getRegionId(),
            now: $now,
            excludePropertyId: $excludePropertyId,
            excludeCitySlugs: $scope?->excludeCitySlugs ?? [],
        );
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

        return $this->scopeSettingsRepository->findActiveByCityId(
            $this->resolveApartmentTariffCityId($property),
        );
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

        return $this->levelPriceRepository->findActiveByCityId(
            $this->resolveApartmentTariffCityId($property),
        );
    }

    /**
     * @return array{
     *     scope: array{tariffCityId: int, tariffCityName: string, locationLabel: string},
     *     levels: list<array<string, mixed>>,
     *     freeTier: array{catalogPositionFrom: ?int, catalogPositionTo: ?int, catalogListingsAtLevel: ?int}
     * }
     */
    public function buildPlacementLevelsPayloadForProperty(Property $property): array
    {
        if ($property->getType() === PropertyType::House->value) {
            $regionId = $this->resolveRegionId($property);
            if ($regionId === null) {
                return $this->emptyPlacementLevelsPayload('области');
            }

            $levelPrices = $this->levelPriceRepository->findActiveByRegionId($regionId);
            $countsByLevel = $this->propertyRepository->countPublishedByEffectiveLevel(
                PropertyType::House->value,
                null,
                $regionId,
            );
            $city = $this->cityRepository->findById($property->getCityId());
            $locationLabel = $city?->getRegionDistrict()?->getRegion()?->getName() ?? 'области';

            return $this->serializePlacementLevelsPayload(
                tariffCityId: null,
                tariffCityName: null,
                locationLabel: $locationLabel,
                levelPrices: $levelPrices,
                countsByLevel: $countsByLevel,
                apartmentScope: null,
                maxLevel: $this->resolveMaxLevelForProperty($property),
            );
        }

        $scope = $this->apartmentPlacementScopeResolver->resolveForCityId($property->getCityId());
        if ($scope === null) {
            $city = $this->cityRepository->findById($property->getCityId());

            return $this->emptyPlacementLevelsPayload($city?->getName() ?? 'города');
        }

        $tariffCity = $this->cityRepository->findById($scope->tariffCityId);
        $levelPrices = $this->levelPriceRepository->findActiveByCityId($scope->tariffCityId);
        $countsByLevel = $this->propertyRepository->countPublishedByEffectiveLevel(
            PropertyType::Apartment->value,
            $scope->catalogCityId,
            $scope->catalogRegionId,
            $scope->excludeCitySlugs,
        );

        return $this->serializePlacementLevelsPayload(
            tariffCityId: $scope->tariffCityId,
            tariffCityName: $tariffCity?->getName() ?? '',
            locationLabel: $scope->locationLabel,
            levelPrices: $levelPrices,
            countsByLevel: $countsByLevel,
            apartmentScope: $scope,
            maxLevel: $this->resolveMaxLevelForProperty($property),
        );
    }

    private function resolveApartmentTariffCityId(Property $property): int
    {
        $scope = $this->apartmentPlacementScopeResolver->resolveForCityId($property->getCityId());

        return $scope?->tariffCityId ?? $property->getCityId();
    }

    /**
     * @param PropertyPlacementLevelPrice[] $levelPrices
     * @param array<int, int>               $countsByLevel
     *
     * @return array{
     *     scope: array{tariffCityId: ?int, tariffCityName: ?string, locationLabel: string},
     *     levels: list<array<string, mixed>>,
     *     freeTier: array{catalogPositionFrom: ?int, catalogPositionTo: ?int, catalogListingsAtLevel: ?int}
     * }
     */
    private function serializePlacementLevelsPayload(
        ?int $tariffCityId,
        ?string $tariffCityName,
        string $locationLabel,
        array $levelPrices,
        array $countsByLevel,
        ?ApartmentPlacementScope $apartmentScope,
        int $maxLevel,
    ): array {
        $levels = array_map(
            static fn (PropertyPlacementLevelPrice $levelPrice) => $levelPrice->getLevel(),
            $levelPrices,
        );
        $bands = $this->catalogPositionBands(
            $countsByLevel,
            array_values(array_unique([...$levels, 0])),
        );
        $freeBand = $bands[0] ?? null;

        $serializedLevels = [];
        foreach ($levelPrices as $levelPrice) {
            $band = $bands[$levelPrice->getLevel()] ?? null;
            $occupied = $this->getLevelPriceOccupancy(
                $levelPrice,
                apartmentScope: $apartmentScope,
            );
            $capacity = $levelPrice->getCapacity();
            $serializedLevels[] = [
                'id' => $levelPrice->getId(),
                'propertyType' => $levelPrice->getPropertyType(),
                'cityId' => $levelPrice->getCityId(),
                'regionId' => $levelPrice->getRegionId(),
                'level' => $levelPrice->getLevel(),
                'label' => $levelPrice->getLabel(),
                'capacity' => $capacity,
                'occupied' => $occupied,
                'available' => $capacity !== null ? max(0, $capacity - $occupied) : null,
                'priceBynPerMonth' => $levelPrice->getPriceBynPerMonth(),
                'catalogPositionFrom' => $band['from'] ?? null,
                'catalogPositionTo' => $band['to'] ?? null,
                'catalogListingsAtLevel' => $band['count'] ?? null,
            ];
        }

        return [
            'scope' => [
                'tariffCityId' => $tariffCityId,
                'tariffCityName' => $tariffCityName,
                'locationLabel' => $locationLabel,
                'maxLevel' => $maxLevel,
            ],
            'levels' => $serializedLevels,
            'freeTier' => [
                'catalogPositionFrom' => $freeBand['from'] ?? null,
                'catalogPositionTo' => $freeBand['to'] ?? null,
                'catalogListingsAtLevel' => $freeBand['count'] ?? null,
            ],
        ];
    }

    /**
     * @return array{
     *     scope: array{tariffCityId: ?int, tariffCityName: ?string, locationLabel: string},
     *     levels: list<array<string, mixed>>,
     *     freeTier: array{catalogPositionFrom: ?int, catalogPositionTo: ?int, catalogListingsAtLevel: ?int}
     * }
     */
    private function emptyPlacementLevelsPayload(string $locationLabel): array
    {
        return [
            'scope' => [
                'tariffCityId' => null,
                'tariffCityName' => null,
                'locationLabel' => $locationLabel,
                'maxLevel' => PropertyPlacementScopeSettings::DEFAULT_MAX_LEVEL,
            ],
            'levels' => [],
            'freeTier' => [
                'catalogPositionFrom' => null,
                'catalogPositionTo' => null,
                'catalogListingsAtLevel' => null,
            ],
        ];
    }

    /**
     * One free VIP 1 (2 weeks) per account: true if the owner has not used it yet.
     * Also checks property history so a stale/cleared user flag cannot grant a second trial.
     */
    public function shouldGrantFreeTrial(Property $property): bool
    {
        $user = $this->userRepository->findById($property->getOwnerId());
        if ($user === null) {
            return false;
        }

        if ($user->hasUsedFreePlacementTrial()) {
            return false;
        }

        if ($this->propertyRepository->ownerHasFreeTrialHistory($property->getOwnerId(), $property->getId())) {
            // Heal desynced flag (e.g. admin reset) when a listing already consumed the trial.
            $user->markFreePlacementTrialUsed();
            $this->userRepository->save($user);

            return false;
        }

        return true;
    }

    /**
     * Mark the owner account as having consumed the one-time free VIP 1 (2 weeks).
     * Call only after free VIP 1 was actually applied to a listing.
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

    /**
     * Catalog position bands per VIP level from current published counts
     * (higher effective levels occupy earlier positions; shuffle rotates within a level).
     *
     * @param array<int, int> $countsByEffectiveLevel effectiveLevel => published count
     * @param list<int>       $levels                 VIP levels to describe (e.g. 1..5)
     *
     * @return array<int, array{from: int, to: int, count: int}>
     */
    public function catalogPositionBands(array $countsByEffectiveLevel, array $levels): array
    {
        if ($levels === []) {
            return [];
        }

        $maxLevel = max($levels);
        $aboveCap = 0;
        foreach ($countsByEffectiveLevel as $effective => $count) {
            if ($effective > $maxLevel) {
                $aboveCap += $count;
            }
        }

        $bands = [];
        foreach ($levels as $level) {
            $above = $aboveCap;
            for ($higher = $level + 1; $higher <= $maxLevel; ++$higher) {
                $above += $countsByEffectiveLevel[$higher] ?? 0;
            }

            $at = $countsByEffectiveLevel[$level] ?? 0;
            if ($at > 0) {
                $bands[$level] = [
                    'from' => $above + 1,
                    'to' => $above + $at,
                    'count' => $at,
                ];
            } else {
                // Empty level: a new listing would sit alone just after higher levels.
                $bands[$level] = [
                    'from' => $above + 1,
                    'to' => $above + 1,
                    'count' => 0,
                ];
            }
        }

        return $bands;
    }
}
