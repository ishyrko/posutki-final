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
        $purchaseRepository->method('findActiveLevelByPropertyId')->willReturn($upgradePurchase);
        $purchaseRepository->method('countOccupiedForLevelPrice')->willReturn(0);

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
    }

    public function testActivatePurchaseExtendsAnchorExpiryOnRenewal(): void
    {
        $ownerId = Id::fromInt(7);
        $property = $this->createProperty($ownerId);
        $now = new \DateTimeImmutable('2026-07-16 12:00:00');
        $anchorExpiresAt = $now->modify('+3 months');

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
        $purchaseRepository->method('findActiveLevelByPropertyId')->willReturn($renewalPurchase);
        $purchaseRepository->method('countOccupiedForLevelPrice')->willReturn(0);

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
