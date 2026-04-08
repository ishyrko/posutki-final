<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Service;

use App\Domain\Property\Entity\MetroStation;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\MetroStationRepositoryInterface;
use App\Domain\Property\Repository\PropertyMetroStationRepositoryInterface;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Service\MetroProximityCalculator;
use PHPUnit\Framework\TestCase;

final class MetroProximityCalculatorTest extends TestCase
{
    public function testSetsNearMetroTrueWhenStationWithinOneKilometer(): void
    {
        $stationRepository = $this->createStub(MetroStationRepositoryInterface::class);
        $propertyMetroStationRepository = $this->createMock(PropertyMetroStationRepositoryInterface::class);

        $property = $this->createPropertyWithId(100);

        $stationRepository
            ->method('findAll')
            ->willReturn([
                new MetroStation(1, 1, 'Nemiga', 'nemiga', 1, 1, 53.9040, 27.5550),
            ]);

        $propertyMetroStationRepository
            ->expects(self::once())
            ->method('save');

        $propertyMetroStationRepository
            ->expects(self::once())
            ->method('deleteByPropertyId')
            ->with(100);

        $service = new MetroProximityCalculator($stationRepository, $propertyMetroStationRepository);
        $service->syncForProperty($property);

        self::assertTrue($property->isNearMetro());
    }

    public function testDoesNotSaveStationsOutsideOneKilometer(): void
    {
        $stationRepository = $this->createStub(MetroStationRepositoryInterface::class);
        $propertyMetroStationRepository = $this->createMock(PropertyMetroStationRepositoryInterface::class);

        $property = $this->createPropertyWithId(101);

        $stationRepository
            ->method('findAll')
            ->willReturn([
                new MetroStation(1, 1, 'Far station', 'far-station', 1, 1, 53.9500, 27.6500),
            ]);

        $propertyMetroStationRepository
            ->expects(self::never())
            ->method('save');

        $service = new MetroProximityCalculator($stationRepository, $propertyMetroStationRepository);
        $service->syncForProperty($property);

        self::assertFalse($property->isNearMetro());
    }

    public function testSetsNearMetroTrueWhenAtLeastOneNearbyStationExists(): void
    {
        $stationRepository = $this->createStub(MetroStationRepositoryInterface::class);
        $propertyMetroStationRepository = $this->createMock(PropertyMetroStationRepositoryInterface::class);

        $property = $this->createPropertyWithId(102);

        $stationRepository
            ->method('findAll')
            ->willReturn([
                new MetroStation(1, 1, 'Far station', 'far-station', 1, 1, 53.9500, 27.6500),
                new MetroStation(2, 1, 'Near station', 'near-station', 1, 2, 53.9042, 27.5608),
            ]);

        $propertyMetroStationRepository
            ->expects(self::once())
            ->method('save');

        $service = new MetroProximityCalculator($stationRepository, $propertyMetroStationRepository);
        $service->syncForProperty($property);

        self::assertTrue($property->isNearMetro());
    }

    private function createPropertyWithId(int $id): Property
    {
        $property = new Property(
            ownerId: Id::fromInt(1),
            type: 'apartment',
            dealType: 'sale',
            title: 'Metro proximity test property',
            description: 'Property used for metro proximity calculator tests and mocks.',
            price: Price::fromAmount(10000000, 'BYN'),
            area: 60.0,
            rooms: 2,
            floor: 3,
            totalFloors: 9,
            bathrooms: null,
            yearBuilt: null,
            renovation: null,
            balcony: null,
            livingArea: null,
            kitchenArea: null,
            dealConditions: null,
            maxDailyGuests: null,
            dailyBedCount: null,
            checkInTime: null,
            checkOutTime: null,
            address: Address::create('10', null),
            cityId: 1,
            coordinates: Coordinates::create(53.9045, 27.5615),
        );

        $reflection = new \ReflectionProperty($property, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($property, Id::fromInt($id));

        return $property;
    }
}
