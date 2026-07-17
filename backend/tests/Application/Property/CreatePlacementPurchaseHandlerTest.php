<?php

declare(strict_types=1);

namespace App\Tests\Application\Property;

use App\Application\Command\Property\CreatePlacementPurchase\CreatePlacementPurchaseCommand;
use App\Application\Command\Property\CreatePlacementPurchase\CreatePlacementPurchaseHandler;
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
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CreatePlacementPurchaseHandlerTest extends TestCase
{
    private Id $ownerId;
    private Property $property;
    /** @var array<int, PropertyPlacementLevelPrice> */
    private array $levelPrices = [];

    protected function setUp(): void
    {
        $this->ownerId = Id::fromInt(7);
        $this->property = $this->createProperty($this->ownerId);
        $this->levelPrices = [
            1 => $this->createLevelPrice(1, 49, id: 101),
            2 => $this->createLevelPrice(2, 119, id: 102),
            3 => $this->createLevelPrice(3, 159, id: 103),
        ];
    }

    public function testCreatesNewLevelPurchaseAtFullPriceWhenNoAnchor(): void
    {
        $handler = $this->createHandler(anchor: null);

        $result = $handler(new CreatePlacementPurchaseCommand(
            propertyId: '10',
            userId: (string) $this->ownerId->getValue(),
            kind: PlacementPurchaseKind::Level->value,
            level: 1,
            durationMonths: 3,
        ));

        self::assertSame(147, $result['priceByn']);
        self::assertSame(1, $result['level']);
        self::assertSame(3, $result['durationMonths']);
        self::assertSame(PlacementPurchaseStatus::PendingPayment->value, $result['status']);
    }

    public function testThrowsWhenDowngradingBelowActiveLevel(): void
    {
        $anchor = $this->createActiveAnchorPurchase(
            level: 3,
            levelPriceId: 103,
            expiresAt: new \DateTimeImmutable('+2 months'),
            id: 50,
        );

        $handler = $this->createHandler(anchor: $anchor, expectSave: false);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Понижение VIP-уровня недоступно');

        $handler(new CreatePlacementPurchaseCommand(
            propertyId: '10',
            userId: (string) $this->ownerId->getValue(),
            kind: PlacementPurchaseKind::Level->value,
            level: 1,
            durationMonths: 1,
        ));
    }

    public function testCreatesRenewalPurchaseWithFullPriceForAddedDuration(): void
    {
        $anchor = $this->createActiveAnchorPurchase(
            level: 2,
            levelPriceId: 102,
            expiresAt: new \DateTimeImmutable('+3 months'),
            id: 51,
        );

        $savedPurchase = null;
        $handler = $this->createHandler(
            anchor: $anchor,
            onSave: static function (PropertyPlacementPurchase $purchase) use (&$savedPurchase): void {
                $savedPurchase = $purchase;
                $idReflection = new \ReflectionProperty($purchase, 'id');
                $idReflection->setAccessible(true);
                $idReflection->setValue($purchase, 99);
            },
        );

        $result = $handler(new CreatePlacementPurchaseCommand(
            propertyId: '10',
            userId: (string) $this->ownerId->getValue(),
            kind: PlacementPurchaseKind::Level->value,
            level: 2,
            durationMonths: 3,
        ));

        self::assertSame(357, $result['priceByn']);
        self::assertNotNull($savedPurchase);
        self::assertSame(51, $savedPurchase->getBasePurchaseId());
    }

    public function testThrowsWhenRenewalExceedsTwelveMonthCap(): void
    {
        $anchor = $this->createActiveAnchorPurchase(
            level: 2,
            levelPriceId: 102,
            expiresAt: new \DateTimeImmutable('+10 months'),
            id: 52,
        );

        $handler = $this->createHandler(anchor: $anchor, expectSave: false);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Продление недоступно на 6 мес.');

        $handler(new CreatePlacementPurchaseCommand(
            propertyId: '10',
            userId: (string) $this->ownerId->getValue(),
            kind: PlacementPurchaseKind::Level->value,
            level: 2,
            durationMonths: 6,
        ));
    }

    public function testCreatesUpgradePurchaseWithProratedPrice(): void
    {
        $anchorExpiresAt = new \DateTimeImmutable('+30 days');
        $anchor = $this->createActiveAnchorPurchase(
            level: 2,
            levelPriceId: 102,
            expiresAt: $anchorExpiresAt,
            id: 53,
        );

        $savedPurchase = null;
        $handler = $this->createHandler(
            anchor: $anchor,
            onSave: static function (PropertyPlacementPurchase $purchase) use (&$savedPurchase): void {
                $savedPurchase = $purchase;
            },
        );

        $result = $handler(new CreatePlacementPurchaseCommand(
            propertyId: '10',
            userId: (string) $this->ownerId->getValue(),
            kind: PlacementPurchaseKind::Level->value,
            level: 3,
            durationMonths: 1,
        ));

        // diff = 159 - 119 = 40 BYN/month, 30 days => round(40 * 30 / 30) = 40
        self::assertSame(40, $result['priceByn']);
        self::assertNotNull($savedPurchase);
        self::assertSame(53, $savedPurchase->getBasePurchaseId());
    }

    public function testCreatesBoostPurchaseWithTwiceDailyLevelGap(): void
    {
        $baseLevelReflection = new \ReflectionProperty($this->property, 'placementBaseLevel');
        $baseLevelReflection->setAccessible(true);
        $baseLevelReflection->setValue($this->property, 1);

        $savedPurchase = null;
        $handler = $this->createHandler(
            anchor: null,
            onSave: static function (PropertyPlacementPurchase $purchase) use (&$savedPurchase): void {
                $savedPurchase = $purchase;
            },
        );

        $result = $handler(new CreatePlacementPurchaseCommand(
            propertyId: '10',
            userId: (string) $this->ownerId->getValue(),
            kind: PlacementPurchaseKind::Boost->value,
        ));

        // (119 - 49) / 30 * 2 = 4.666... → ceil 5
        self::assertSame(5, $result['priceByn']);
        self::assertSame(PlacementPurchaseKind::Boost->value, $result['kind']);
        self::assertNotNull($savedPurchase);
        self::assertSame(5, $savedPurchase->getPriceByn());
    }

    private function createHandler(
        ?PropertyPlacementPurchase $anchor,
        ?callable $onSave = null,
        bool $expectSave = true,
    ): CreatePlacementPurchaseHandler {
        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->willReturn($this->property);

        $purchaseRepository = $this->createMock(PropertyPlacementPurchaseRepositoryInterface::class);
        $purchaseRepository->method('findActiveLevelByPropertyId')->willReturn($anchor);
        $purchaseRepository->method('countOccupiedForLevelPrice')->willReturn(0);
        if ($expectSave) {
            $purchaseRepository
                ->expects(self::once())
                ->method('save')
                ->with(self::isInstanceOf(PropertyPlacementPurchase::class))
                ->willReturnCallback(function (PropertyPlacementPurchase $purchase) use ($onSave): void {
                    if ($onSave !== null) {
                        $onSave($purchase);
                    }
                });
        } else {
            $purchaseRepository->expects(self::never())->method('save');
        }

        $levelPriceRepository = $this->createMock(PropertyPlacementLevelPriceRepositoryInterface::class);
        $levelPriceRepository->method('findById')->willReturnCallback(
            fn (int $id): ?PropertyPlacementLevelPrice => match ($id) {
                101 => $this->levelPrices[1],
                102 => $this->levelPrices[2],
                103 => $this->levelPrices[3],
                default => null,
            },
        );
        $levelPriceRepository->method('findActiveByCityId')->willReturn(array_values($this->levelPrices));

        $placementService = new PropertyPlacementService(
            $propertyRepository,
            $purchaseRepository,
            $levelPriceRepository,
            $this->createStub(PropertyPlacementScopeSettingsRepositoryInterface::class),
            $this->createStub(CityRepositoryInterface::class),
            $this->createStub(UserRepositoryInterface::class),
        );

        return new CreatePlacementPurchaseHandler(
            $propertyRepository,
            $purchaseRepository,
            $placementService,
        );
    }

    private function createProperty(Id $ownerId): Property
    {
        $property = new Property(
            ownerId: $ownerId,
            type: 'apartment',
            dealType: 'daily',
            title: 'Test property title for placement',
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

        $idReflection = new \ReflectionProperty($property, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($property, Id::fromInt(10));

        return $property;
    }

    private function createLevelPrice(int $level, int $priceBynPerMonth, int $id): PropertyPlacementLevelPrice
    {
        $levelPrice = new PropertyPlacementLevelPrice(
            propertyType: 'apartment',
            cityId: 1,
            regionId: null,
            level: $level,
            priceBynPerMonth: $priceBynPerMonth,
        );

        $idReflection = new \ReflectionProperty($levelPrice, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($levelPrice, $id);

        return $levelPrice;
    }

    private function createActiveAnchorPurchase(
        int $level,
        int $levelPriceId,
        \DateTimeImmutable $expiresAt,
        int $id,
    ): PropertyPlacementPurchase {
        $purchase = new PropertyPlacementPurchase(
            propertyId: 10,
            ownerId: $this->ownerId,
            kind: PlacementPurchaseKind::Level->value,
            priceByn: 119,
            source: 'self_service',
            level: $level,
            levelPriceId: $levelPriceId,
            durationMonths: 1,
        );
        $purchase->activate(null, new \DateTimeImmutable('-1 month'), $expiresAt);

        $idReflection = new \ReflectionProperty($purchase, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($purchase, $id);

        return $purchase;
    }
}
