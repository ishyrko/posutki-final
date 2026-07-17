<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Application\Service\PropertyPlacementService;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyPlacementLevelPrice;
use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Enum\PlacementPurchaseKind;
use App\Domain\Property\Enum\PlacementPurchaseStatus;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementLevelPriceRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementScopeSettingsRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class PropertyPlacementServiceTest extends TestCase
{
    public function testActivatePurchaseSupersedesAnchorOnUpgrade(): void
    {
        $ownerId = Id::fromInt(7);
        $property = $this->createProperty($ownerId);
        $now = new \DateTimeImmutable('2026-07-16 12:00:00');
        $anchorExpiresAt = $now->modify('+2 months');
        $this->setPlacementState($property, level: 2, expiresAt: $anchorExpiresAt);

        $anchor = new PropertyPlacementPurchase(
            propertyId: 10,
            ownerId: $ownerId,
            kind: PlacementPurchaseKind::Level->value,
            priceByn: 119,
            source: 'self_service',
            level: 2,
            levelPriceId: 102,
            durationMonths: 1,
        );
        $anchor->activate(null, $now->modify('-1 month'), $anchorExpiresAt);
        $this->setEntityId($anchor, 50);

        $upgradePurchase = new PropertyPlacementPurchase(
            propertyId: 10,
            ownerId: $ownerId,
            kind: PlacementPurchaseKind::Level->value,
            priceByn: 40,
            source: 'self_service',
            level: 3,
            levelPriceId: 103,
            durationMonths: 1,
            basePurchaseId: 50,
        );

        $levelPrice = new PropertyPlacementLevelPrice('apartment', 1, null, 3, 159);
        $this->setEntityId($levelPrice, 103);

        $purchaseRepository = $this->createMock(PropertyPlacementPurchaseRepositoryInterface::class);
        $purchaseRepository->method('findById')->with(50)->willReturn($anchor);
        $purchaseRepository->expects(self::exactly(2))->method('save');

        $levelPriceRepository = $this->createMock(PropertyPlacementLevelPriceRepositoryInterface::class);
        $levelPriceRepository->method('findById')->with(103)->willReturn($levelPrice);

        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->expects(self::atLeastOnce())->method('save')->with($property);

        $service = new PropertyPlacementService(
            $propertyRepository,
            $purchaseRepository,
            $levelPriceRepository,
            $this->createStub(PropertyPlacementScopeSettingsRepositoryInterface::class),
            $this->createStub(CityRepositoryInterface::class),
            $this->createStub(UserRepositoryInterface::class),
        );

        $service->activatePurchase($upgradePurchase, $property, null, $now);

        self::assertSame(PlacementPurchaseStatus::Superseded->value, $anchor->getStatus());
        self::assertSame(PlacementPurchaseStatus::Active->value, $upgradePurchase->getStatus());
        self::assertEquals($anchorExpiresAt, $upgradePurchase->getExpiresAt());
        self::assertSame(3, $property->getPlacementBaseLevel());
        self::assertEquals($anchorExpiresAt, $property->getPlacementLevelExpiresAt());
    }

    public function testActivatePurchaseExtendsAnchorExpiryOnRenewal(): void
    {
        $ownerId = Id::fromInt(7);
        $property = $this->createProperty($ownerId);
        $now = new \DateTimeImmutable('2026-07-16 12:00:00');
        $anchorExpiresAt = $now->modify('+3 months');
        $this->setPlacementState($property, level: 2, expiresAt: $anchorExpiresAt);

        $anchor = new PropertyPlacementPurchase(
            propertyId: 10,
            ownerId: $ownerId,
            kind: PlacementPurchaseKind::Level->value,
            priceByn: 119,
            source: 'self_service',
            level: 2,
            levelPriceId: 102,
            durationMonths: 1,
        );
        $anchor->activate(null, $now->modify('-1 month'), $anchorExpiresAt);
        $this->setEntityId($anchor, 60);

        $renewalPurchase = new PropertyPlacementPurchase(
            propertyId: 10,
            ownerId: $ownerId,
            kind: PlacementPurchaseKind::Level->value,
            priceByn: 357,
            source: 'self_service',
            level: 2,
            levelPriceId: 102,
            durationMonths: 3,
            basePurchaseId: 60,
        );

        $levelPrice = new PropertyPlacementLevelPrice('apartment', 1, null, 2, 119);
        $this->setEntityId($levelPrice, 102);

        $purchaseRepository = $this->createMock(PropertyPlacementPurchaseRepositoryInterface::class);
        $purchaseRepository->method('findById')->with(60)->willReturn($anchor);
        $purchaseRepository->expects(self::exactly(2))->method('save');

        $levelPriceRepository = $this->createMock(PropertyPlacementLevelPriceRepositoryInterface::class);
        $levelPriceRepository->method('findById')->with(102)->willReturn($levelPrice);

        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->expects(self::atLeastOnce())->method('save')->with($property);

        $service = new PropertyPlacementService(
            $propertyRepository,
            $purchaseRepository,
            $levelPriceRepository,
            $this->createStub(PropertyPlacementScopeSettingsRepositoryInterface::class),
            $this->createStub(CityRepositoryInterface::class),
            $this->createStub(UserRepositoryInterface::class),
        );

        $service->activatePurchase($renewalPurchase, $property, null, $now);

        $expectedExpiresAt = $anchorExpiresAt->modify('+3 months');

        self::assertSame(PlacementPurchaseStatus::Superseded->value, $anchor->getStatus());
        self::assertEquals($expectedExpiresAt, $renewalPurchase->getExpiresAt());
        self::assertSame(2, $property->getPlacementBaseLevel());
        self::assertEquals($expectedExpiresAt, $property->getPlacementLevelExpiresAt());
    }

    public function testQuoteBoostPurchaseIsTwiceDailyGapToNextLevel(): void
    {
        $ownerId = Id::fromInt(7);
        $property = $this->createProperty($ownerId);
        $this->setPlacementBaseLevel($property, 2);

        $levelPrices = [
            new PropertyPlacementLevelPrice('apartment', 1, null, 1, 49),
            new PropertyPlacementLevelPrice('apartment', 1, null, 2, 119),
            new PropertyPlacementLevelPrice('apartment', 1, null, 3, 159),
        ];

        $levelPriceRepository = $this->createMock(PropertyPlacementLevelPriceRepositoryInterface::class);
        $levelPriceRepository->method('findActiveByCityId')->willReturn($levelPrices);

        $scopeSettingsRepository = $this->createMock(PropertyPlacementScopeSettingsRepositoryInterface::class);
        $scopeSettingsRepository->method('findActiveByCityId')->willReturn(null);

        $service = new PropertyPlacementService(
            $this->createStub(PropertyRepositoryInterface::class),
            $this->createStub(PropertyPlacementPurchaseRepositoryInterface::class),
            $levelPriceRepository,
            $scopeSettingsRepository,
            $this->createStub(CityRepositoryInterface::class),
            $this->createStub(UserRepositoryInterface::class),
        );

        // (159 - 119) / 30 * 2 = 2.666... ? ceil 3
        self::assertSame(3, $service->quoteBoostPurchase($property));
    }

    public function testQuoteBoostPurchaseFromFreeLevelUsesZeroAsCurrentPrice(): void
    {
        $ownerId = Id::fromInt(7);
        $property = $this->createProperty($ownerId);
        $this->setPlacementBaseLevel($property, 0);

        $levelPrices = [
            new PropertyPlacementLevelPrice('apartment', 1, null, 1, 49),
            new PropertyPlacementLevelPrice('apartment', 1, null, 2, 119),
        ];

        $levelPriceRepository = $this->createMock(PropertyPlacementLevelPriceRepositoryInterface::class);
        $levelPriceRepository->method('findActiveByCityId')->willReturn($levelPrices);

        $scopeSettingsRepository = $this->createMock(PropertyPlacementScopeSettingsRepositoryInterface::class);
        $scopeSettingsRepository->method('findActiveByCityId')->willReturn(null);

        $service = new PropertyPlacementService(
            $this->createStub(PropertyRepositoryInterface::class),
            $this->createStub(PropertyPlacementPurchaseRepositoryInterface::class),
            $levelPriceRepository,
            $scopeSettingsRepository,
            $this->createStub(CityRepositoryInterface::class),
            $this->createStub(UserRepositoryInterface::class),
        );

        // (49 - 0) / 30 * 2 = 3.266... ? ceil 4
        self::assertSame(4, $service->quoteBoostPurchase($property));
    }

    public function testActivateBoostKeepsExistingBaseLevelFromProperty(): void
    {
        $ownerId = Id::fromInt(7);
        $property = $this->createProperty($ownerId);
        $now = new \DateTimeImmutable('2026-07-17 10:00:00');

        // The base VIP is stored on the property row and must not be recomputed on boost activation.
        $this->setPlacementBaseLevel($property, 1);

        $boostPurchase = new PropertyPlacementPurchase(
            propertyId: 10,
            ownerId: $ownerId,
            kind: PlacementPurchaseKind::Boost->value,
            priceByn: 4,
            source: 'self_service',
        );

        $purchaseRepository = $this->createMock(PropertyPlacementPurchaseRepositoryInterface::class);
        $purchaseRepository->expects(self::once())->method('save')->with($boostPurchase);
        $purchaseRepository->expects(self::never())->method('findActiveLevelByPropertyId');

        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->expects(self::once())->method('save')->with($property);

        $service = new PropertyPlacementService(
            $propertyRepository,
            $purchaseRepository,
            $this->createStub(PropertyPlacementLevelPriceRepositoryInterface::class),
            $this->createStub(PropertyPlacementScopeSettingsRepositoryInterface::class),
            $this->createStub(CityRepositoryInterface::class),
            $this->createStub(UserRepositoryInterface::class),
        );

        $service->activatePurchase($boostPurchase, $property, null, $now);

        self::assertSame(1, $property->getPlacementBaseLevel());
        self::assertSame(2, $property->getPlacementEffectiveLevel());
    }

    private function setPlacementBaseLevel(Property $property, int $level): void
    {
        $reflection = new \ReflectionProperty($property, 'placementBaseLevel');
        $reflection->setAccessible(true);
        $reflection->setValue($property, $level);
    }

    private function setPlacementState(Property $property, int $level, \DateTimeImmutable $expiresAt): void
    {
        $this->setPlacementBaseLevel($property, $level);

        $expiresReflection = new \ReflectionProperty($property, 'placementLevelExpiresAt');
        $expiresReflection->setAccessible(true);
        $expiresReflection->setValue($property, $expiresAt);

        $effectiveReflection = new \ReflectionProperty($property, 'placementEffectiveLevel');
        $effectiveReflection->setAccessible(true);
        $effectiveReflection->setValue($property, $level);
    }

    private function createProperty(Id $ownerId): Property
    {
        $property = new Property(
            ownerId: $ownerId,
            type: 'apartment',
            dealType: 'daily',
            title: 'Test property title for placement service',
            description: 'Test property description long enough for validation rules.',
            price: Price::fromAmount(100, 'BYN'),
            area: 50.0,
            rooms: 2,
            floor: 3,
            totalFloors: 9,
            bathrooms: 1,
            yearBuilt: 2010,
            renovation: null,
            balcony: null,
            livingArea: null,
            kitchenArea: null,
            dealConditions: null,
            paymentMethods: null,
            maxDailyGuests: 4,
            dailySingleBeds: null,
            dailyDoubleBeds: null,
            checkInTime: null,
            checkOutTime: null,
            address: Address::create('1', null),
            cityId: 1,
            coordinates: Coordinates::create(53.9, 27.5),
            landArea: null,
        );
        $property->setStatus('published');

        $this->setEntityId($property, Id::fromInt(10));

        return $property;
    }

    private function setEntityId(object $entity, int|Id $id): void
    {
        $idReflection = new \ReflectionProperty($entity, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($entity, $id instanceof Id ? $id : $id);
    }
}
