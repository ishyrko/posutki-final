<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\IcsCalendarService;
use App\Application\Service\PropertyCalendarAggregator;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\PropertyAvailabilityBlockRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\ValueObject\Id;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class PropertyCalendarAggregatorTest extends TestCase
{
    public function testGetCalendarLastUpdatedAtUsesLatestManualAndImportedTimestamps(): void
    {
        $property = $this->createProperty();
        $manualAt = new \DateTimeImmutable('2026-06-10T12:00:00+00:00');
        $importedAt = new \DateTimeImmutable('-30 minutes');

        $property->setExternalCalendarUrls(['https://example.com/calendar.ics']);
        $property->setExternalCalendarSnapshot([], $importedAt);

        $availabilityBlockRepository = $this->createStub(PropertyAvailabilityBlockRepositoryInterface::class);
        $availabilityBlockRepository
            ->method('findLatestCreatedAtByPropertyId')
            ->willReturn($manualAt);

        $aggregator = new PropertyCalendarAggregator(
            $availabilityBlockRepository,
            $this->createPropertyRepository(),
            new IcsCalendarService(new MockHttpClient()),
        );

        self::assertSame(
            $importedAt->format('c'),
            $aggregator->getCalendarLastUpdatedAt($property)?->format('c'),
        );
    }

    public function testStaleSnapshotIsRefetchedFromExternalCalendar(): void
    {
        $property = $this->createProperty();
        $property->setExternalCalendarUrls(['https://example.com/calendar.ics']);
        $property->setExternalCalendarSnapshot(
            [['start' => '2026-01-01', 'end' => '2026-01-02']],
            new \DateTimeImmutable('-3 hours'),
        );

        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:test-event-1
DTSTAMP:20260612T120000Z
LAST-MODIFIED:20260612T120000Z
DTSTART;VALUE=DATE:20260626
DTEND;VALUE=DATE:20260628
SUMMARY:Booked
END:VEVENT
END:VCALENDAR
ICS;

        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->expects(self::once())->method('save')->with($property);

        $aggregator = new PropertyCalendarAggregator(
            $this->createStub(PropertyAvailabilityBlockRepositoryInterface::class),
            $propertyRepository,
            new IcsCalendarService(new MockHttpClient([
                new MockResponse($ics, ['http_code' => 200]),
            ])),
        );

        $importedData = $aggregator->resolveImportedCalendarData($property);

        self::assertSame([
            ['start' => '2026-06-26', 'end' => '2026-06-27'],
        ], $importedData['blockedRanges']);
        self::assertNotNull($importedData['lastUpdatedAt']);
    }

    public function testStaleSnapshotIsDiscardedWhenRefreshFails(): void
    {
        $property = $this->createProperty();
        $property->setExternalCalendarUrls(['https://broken.example/calendar.ics']);
        $property->setExternalCalendarSnapshot(
            [['start' => '2026-01-01', 'end' => '2026-01-02']],
            new \DateTimeImmutable('-3 hours'),
        );

        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->expects(self::never())->method('save');

        $aggregator = new PropertyCalendarAggregator(
            $this->createStub(PropertyAvailabilityBlockRepositoryInterface::class),
            $propertyRepository,
            new IcsCalendarService(new MockHttpClient([
                new MockResponse('', ['http_code' => 500]),
            ])),
        );

        $importedData = $aggregator->resolveImportedCalendarData($property);

        self::assertSame([], $importedData['blockedRanges']);
        self::assertNull($importedData['lastUpdatedAt']);
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
            $this->createPropertyRepository(),
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

    private function createPropertyRepository(): PropertyRepositoryInterface
    {
        return $this->createStub(PropertyRepositoryInterface::class);
    }
}
