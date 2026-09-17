<?php

declare(strict_types=1);

namespace App\Tests\Application\Property;

use App\Application\Query\Property\GetPropertyStatsOverview\GetPropertyStatsOverviewHandler;
use App\Application\Query\Property\GetPropertyStatsOverview\GetPropertyStatsOverviewQuery;
use App\Domain\Property\Repository\PropertyStatsAggregateRepositoryInterface;
use App\Domain\Property\ValueObject\PropertyStatsFilter;
use PHPUnit\Framework\TestCase;

final class GetPropertyStatsOverviewHandlerTest extends TestCase
{
    public function testAggregatesDailyStatsAndTotals(): void
    {
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $yesterday = (new \DateTimeImmutable('today'))->modify('-1 day')->format('Y-m-d');

        $repository = $this->createStub(PropertyStatsAggregateRepositoryInterface::class);
        $repository->method('findAggregatedDailyStats')->willReturn([
            ['date' => $yesterday, 'views' => 10, 'phoneViews' => 2],
            ['date' => $today, 'views' => 5, 'phoneViews' => 1],
        ]);
        $repository->method('findAggregatedDailyFavorites')->willReturn([
            ['date' => $today, 'count' => 3],
        ]);
        $repository->method('findAggregatedDailyReceivedMessages')->willReturn([
            ['date' => $today, 'count' => 2],
        ]);
        $repository->method('findAggregatedDailyBookingInquiries')->willReturn([
            ['date' => $yesterday, 'count' => 1],
        ]);
        $repository->method('countProperties')->willReturn(42);

        $handler = new GetPropertyStatsOverviewHandler($repository);
        $result = $handler(new GetPropertyStatsOverviewQuery(
            period: 7,
            propertyType: 'apartment',
            cityId: 1,
        ));

        self::assertSame(7, $result['period']);
        self::assertSame('apartment', $result['propertyType']);
        self::assertSame(1, $result['cityId']);
        self::assertNull($result['ownerId']);
        self::assertSame(42, $result['propertiesCount']);
        self::assertSame(15, $result['totals']['views']);
        self::assertSame(3, $result['totals']['phoneViews']);
        self::assertSame(3, $result['totals']['favorites']);
        self::assertSame(2, $result['totals']['messages']);
        self::assertSame(1, $result['totals']['bookingInquiries']);
        self::assertCount(7, $result['daily']);

        $dailyByDate = array_column($result['daily'], null, 'date');
        self::assertSame(10, $dailyByDate[$yesterday]['views']);
        self::assertSame(5, $dailyByDate[$today]['views']);
        self::assertSame(3, $dailyByDate[$today]['favorites']);
        self::assertSame(2, $dailyByDate[$today]['messages']);
        self::assertSame(1, $dailyByDate[$yesterday]['bookingInquiries']);
    }

    public function testNormalizesInvalidFilters(): void
    {
        $repository = $this->createStub(PropertyStatsAggregateRepositoryInterface::class);
        $repository->method('findAggregatedDailyStats')->willReturn([]);
        $repository->method('findAggregatedDailyFavorites')->willReturn([]);
        $repository->method('findAggregatedDailyReceivedMessages')->willReturn([]);
        $repository->method('findAggregatedDailyBookingInquiries')->willReturn([]);
        $repository->method('countProperties')->willReturn(0);

        $handler = new GetPropertyStatsOverviewHandler($repository);
        $result = $handler(new GetPropertyStatsOverviewQuery(
            period: 14,
            propertyType: 'invalid',
            cityId: null,
        ));

        self::assertSame(30, $result['period']);
        self::assertNull($result['propertyType']);
    }

    public function testSupportsTodayAndYesterdayPeriods(): void
    {
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $yesterday = (new \DateTimeImmutable('today'))->modify('-1 day')->format('Y-m-d');

        $repository = $this->createStub(PropertyStatsAggregateRepositoryInterface::class);
        $repository->method('findAggregatedDailyStats')->willReturn([
            ['date' => $today, 'views' => 4, 'phoneViews' => 1],
            ['date' => $yesterday, 'views' => 6, 'phoneViews' => 2],
        ]);
        $repository->method('findAggregatedDailyFavorites')->willReturn([]);
        $repository->method('findAggregatedDailyReceivedMessages')->willReturn([]);
        $repository->method('findAggregatedDailyBookingInquiries')->willReturn([]);
        $repository->method('countProperties')->willReturn(0);

        $handler = new GetPropertyStatsOverviewHandler($repository);

        $todayResult = $handler(new GetPropertyStatsOverviewQuery(period: 0, propertyType: null, cityId: null));
        self::assertSame(0, $todayResult['period']);
        self::assertCount(1, $todayResult['daily']);
        self::assertSame($today, $todayResult['daily'][0]['date']);
        self::assertSame(4, $todayResult['totals']['views']);

        $yesterdayResult = $handler(new GetPropertyStatsOverviewQuery(period: -1, propertyType: null, cityId: null));
        self::assertSame(-1, $yesterdayResult['period']);
        self::assertCount(1, $yesterdayResult['daily']);
        self::assertSame($yesterday, $yesterdayResult['daily'][0]['date']);
        self::assertSame(6, $yesterdayResult['totals']['views']);
    }

    public function testSupportsCustomDateRange(): void
    {
        $repository = $this->createStub(PropertyStatsAggregateRepositoryInterface::class);
        $repository->method('findAggregatedDailyStats')->willReturn([
            ['date' => '2026-07-01', 'views' => 3, 'phoneViews' => 1],
            ['date' => '2026-07-03', 'views' => 7, 'phoneViews' => 2],
        ]);
        $repository->method('findAggregatedDailyFavorites')->willReturn([]);
        $repository->method('findAggregatedDailyReceivedMessages')->willReturn([]);
        $repository->method('findAggregatedDailyBookingInquiries')->willReturn([]);
        $repository->method('countProperties')->willReturn(10);

        $handler = new GetPropertyStatsOverviewHandler($repository);
        $result = $handler(new GetPropertyStatsOverviewQuery(
            period: 30,
            propertyType: null,
            cityId: null,
            dateFrom: '2026-07-01',
            dateTo: '2026-07-03',
        ));

        self::assertNull($result['period']);
        self::assertSame('2026-07-01', $result['dateFrom']);
        self::assertSame('2026-07-03', $result['dateTo']);
        self::assertCount(3, $result['daily']);
        self::assertSame(10, $result['totals']['views']);
    }

    public function testFallsBackToDefaultPeriodForInvalidCustomRange(): void
    {
        $repository = $this->createStub(PropertyStatsAggregateRepositoryInterface::class);
        $repository->method('findAggregatedDailyStats')->willReturn([]);
        $repository->method('findAggregatedDailyFavorites')->willReturn([]);
        $repository->method('findAggregatedDailyReceivedMessages')->willReturn([]);
        $repository->method('findAggregatedDailyBookingInquiries')->willReturn([]);
        $repository->method('countProperties')->willReturn(0);

        $handler = new GetPropertyStatsOverviewHandler($repository);
        $result = $handler(new GetPropertyStatsOverviewQuery(
            period: 7,
            propertyType: null,
            cityId: null,
            dateFrom: '2026-07-10',
            dateTo: '2026-07-01',
        ));

        self::assertSame(7, $result['period']);
        self::assertNull($result['dateFrom']);
        self::assertNull($result['dateTo']);
        self::assertCount(7, $result['daily']);
    }

    public function testPassesOwnerIdToRepository(): void
    {
        $ownerId = 15;
        $matchesOwner = static fn (PropertyStatsFilter $filter): bool => $filter->ownerId === $ownerId
            && $filter->propertyType === null
            && $filter->cityId === null
            && $filter->regionId === null;

        $repository = $this->createMock(PropertyStatsAggregateRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findAggregatedDailyStats')
            ->with(
                self::isInstanceOf(\DateTimeImmutable::class),
                self::isInstanceOf(\DateTimeImmutable::class),
                self::callback($matchesOwner),
            )
            ->willReturn([]);
        $repository->expects(self::once())
            ->method('findAggregatedDailyFavorites')
            ->with(
                self::isInstanceOf(\DateTimeImmutable::class),
                self::isInstanceOf(\DateTimeImmutable::class),
                self::callback($matchesOwner),
            )
            ->willReturn([]);
        $repository->expects(self::once())
            ->method('findAggregatedDailyReceivedMessages')
            ->with(
                self::isInstanceOf(\DateTimeImmutable::class),
                self::isInstanceOf(\DateTimeImmutable::class),
                self::callback($matchesOwner),
            )
            ->willReturn([]);
        $repository->expects(self::once())
            ->method('findAggregatedDailyBookingInquiries')
            ->with(
                self::isInstanceOf(\DateTimeImmutable::class),
                self::isInstanceOf(\DateTimeImmutable::class),
                self::callback($matchesOwner),
            )
            ->willReturn([]);
        $repository->expects(self::once())
            ->method('countProperties')
            ->with(self::callback($matchesOwner))
            ->willReturn(3);

        $handler = new GetPropertyStatsOverviewHandler($repository);
        $result = $handler(new GetPropertyStatsOverviewQuery(
            period: 7,
            propertyType: null,
            cityId: null,
            ownerId: $ownerId,
        ));

        self::assertSame($ownerId, $result['ownerId']);
        self::assertSame(3, $result['propertiesCount']);
    }
}
