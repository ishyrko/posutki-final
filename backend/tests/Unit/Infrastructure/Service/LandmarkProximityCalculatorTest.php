<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Service;

use App\Domain\Property\Entity\Landmark;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\LandmarkRepositoryInterface;
use App\Domain\Property\Repository\PropertyLandmarkRepositoryInterface;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Service\LandmarkProximityCalculator;
use PHPUnit\Framework\TestCase;

final class LandmarkProximityCalculatorTest extends TestCase
{
    public function testSavesLandmarkWhenPropertyWithinRadius(): void
    {
        $landmarkRepository = $this->createStub(LandmarkRepositoryInterface::class);
        $propertyLandmarkRepository = $this->createMock(PropertyLandmarkRepositoryInterface::class);

        $property = $this->createPropertyWithId(100);
        $property->setStreetName('Немига');

        $landmark = $this->createLandmarkWithId(1, 'National Library', 'national-library', 53.9045, 27.5615);

        $landmarkRepository
            ->method('findByCityId')
            ->willReturn([$landmark]);

        $propertyLandmarkRepository
            ->expects(self::once())
            ->method('deleteByPropertyId')
            ->with(100);

        $propertyLandmarkRepository
            ->expects(self::once())
            ->method('save');

        $service = new LandmarkProximityCalculator($landmarkRepository, $propertyLandmarkRepository);
        $service->syncForProperty($property);
    }

    public function testDoesNotSaveLandmarksOutsideRadius(): void
    {
        $landmarkRepository = $this->createStub(LandmarkRepositoryInterface::class);
        $propertyLandmarkRepository = $this->createMock(PropertyLandmarkRepositoryInterface::class);

        $property = $this->createPropertyWithId(101);
        $property->setStreetName('Дальняя');

        $landmark = $this->createLandmarkWithId(1, 'Far landmark', 'far-landmark', 53.9500, 27.6500);

        $landmarkRepository
            ->method('findByCityId')
            ->willReturn([$landmark]);

        $propertyLandmarkRepository
            ->expects(self::once())
            ->method('deleteByPropertyId')
            ->with(101);

        $propertyLandmarkRepository
            ->expects(self::never())
            ->method('save');

        $service = new LandmarkProximityCalculator($landmarkRepository, $propertyLandmarkRepository);
        $service->syncForProperty($property);
    }

    public function testSkipsLandmarksWhenPropertyHasNoStreet(): void
    {
        $landmarkRepository = $this->createStub(LandmarkRepositoryInterface::class);
        $propertyLandmarkRepository = $this->createMock(PropertyLandmarkRepositoryInterface::class);

        $property = $this->createPropertyWithId(103);

        $landmarkRepository
            ->method('findByCityId')
            ->willReturn([
                new Landmark(1, 'Near landmark', 'near-landmark', 'Near landmark', 53.9042, 27.5608),
            ]);

        $propertyLandmarkRepository
            ->expects(self::once())
            ->method('deleteByPropertyId')
            ->with(103);

        $propertyLandmarkRepository
            ->expects(self::never())
            ->method('save');

        $service = new LandmarkProximityCalculator($landmarkRepository, $propertyLandmarkRepository);
        $service->syncForProperty($property);
    }

    public function testUsesFixedProximityRadius(): void
    {
        $landmarkRepository = $this->createStub(LandmarkRepositoryInterface::class);
        $propertyLandmarkRepository = $this->createMock(PropertyLandmarkRepositoryInterface::class);

        $property = $this->createPropertyWithId(102);
        $property->setStreetName('Центральная');

        $farLandmark = $this->createLandmarkWithId(1, 'Far landmark', 'far-landmark', 53.9500, 27.6500);
        $nearLandmark = $this->createLandmarkWithId(2, 'Near landmark', 'near-landmark', 53.9045, 27.5615);

        $landmarkRepository
            ->method('findByCityId')
            ->willReturn([$farLandmark, $nearLandmark]);

        $propertyLandmarkRepository
            ->expects(self::once())
            ->method('save');

        $service = new LandmarkProximityCalculator($landmarkRepository, $propertyLandmarkRepository);
        $service->syncForProperty($property);
    }

    public function testSavesLandmarkWhenInactive(): void
    {
        $landmarkRepository = $this->createStub(LandmarkRepositoryInterface::class);
        $propertyLandmarkRepository = $this->createMock(PropertyLandmarkRepositoryInterface::class);

        $property = $this->createPropertyWithId(104);
        $property->setStreetName('Немига');

        $landmark = $this->createLandmarkWithId(3, 'Inactive landmark', 'inactive-landmark', 53.9045, 27.5615);
        $landmark->setIsActive(false);

        $landmarkRepository
            ->method('findByCityId')
            ->willReturn([$landmark]);

        $propertyLandmarkRepository
            ->expects(self::once())
            ->method('deleteByPropertyId')
            ->with(104);

        $propertyLandmarkRepository
            ->expects(self::once())
            ->method('save');

        $service = new LandmarkProximityCalculator($landmarkRepository, $propertyLandmarkRepository);
        $service->syncForProperty($property);
    }

    private function createPropertyWithId(int $id): Property
    {
        $property = new Property(
            ownerId: Id::fromInt(1),
            type: 'apartment',
            dealType: 'daily',
            title: 'Landmark proximity test property',
            description: 'Property used for landmark proximity calculator tests and mocks.',
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
            paymentMethods: null,
            maxDailyGuests: null,
            dailySingleBeds: null,
            dailyDoubleBeds: null,
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

    private function createLandmarkWithId(
        int $id,
        string $name,
        string $slug,
        float $latitude,
        float $longitude,
    ): Landmark {
        $landmark = new Landmark(1, $name, $slug, $name, $latitude, $longitude);

        $reflection = new \ReflectionProperty($landmark, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($landmark, $id);

        return $landmark;
    }
}
