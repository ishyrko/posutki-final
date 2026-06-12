<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\IcsCalendarService;
use App\Application\Service\PropertyCalendarAggregator;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\PropertyAvailabilityBlockRepositoryInterface;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\ValueObject\Id;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

final class PropertyCalendarAggregatorTest extends TestCase
{
    public function testGetCalendarLastUpdatedAtUsesLatestManualAndImportedTimestamps(): void
    {
        $property = $this->createProperty();
        $manualAt = new \DateTimeImmutable('2026-06-10T12:00:00+00:00');
        $importedAt = new \DateTimeImmutable('2026-06-11T08:00:00+00:00');

        $property->setExternalCalendarSnapshot([], $importedAt);

        $availabilityBlockRepository = $this->createStub(PropertyAvailabilityBlockRepositoryInterface::class);
        $availabilityBlockRepository
            ->method('findLatestCreatedAtByPropertyId')
            ->willReturn($manualAt);

        $aggregator = new PropertyCalendarAggregator(
            $availabilityBlockRepository,
            new IcsCalendarService(new MockHttpClient()),
        );

        self::assertEquals($importedAt, $aggregator->getCalendarLastUpdatedAt($property));
    }

    public function testGetPublicCalendarDataIncludesManualLastUpdatedAt(): void
    {
        $property = $this->createProperty();
        $manualAt = new \DateTimeImmutable('2026-06-10T12:00:00+00:00');

        $availabilityBlockRepository = $this->createStub(PropertyAvailabilityBlockRepositoryInterface::class);
        $availabilityBlockRepository
            ->method('findByPropertyId')
            ->willReturn([]);
        $availabilityBlockRepository
            ->method('findLatestCreatedAtByPropertyId')
            ->willReturn($manualAt);

        $aggregator = new PropertyCalendarAggregator(
            $availabilityBlockRepository,
            new IcsCalendarService(new MockHttpClient()),
        );

        $calendarData = $aggregator->getPublicCalendarData($property);

        self::assertSame($manualAt->format('c'), $calendarData['lastUpdatedAt']);
    }

    private function createProperty(): Property
    {
        $property = new Property(
            ownerId: Id::fromInt(1),
            type: 'apartment',
            dealType: 'daily',
            title: 'Calendar aggregator test listing',
            description: 'Calendar aggregator test listing description with enough length.',
            price: Price::fromAmount(10000000, 'BYN'),
            area: 60.0,
            rooms: 2,
            floor: 3,
            totalFloors: 9,
            bathrooms: 1,
            yearBuilt: 2015,
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
            coordinates: Coordinates::create(53.9, 27.56),
        );

        $idReflection = new \ReflectionProperty($property, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($property, Id::fromInt(20));

        return $property;
    }
}
