<?php

declare(strict_types=1);

namespace App\Tests\Application\Property;

use App\Application\Query\Property\GetAdminPropertyStatsOverview\GetAdminPropertyStatsOverviewHandler;
use App\Application\Query\Property\GetAdminPropertyStatsOverview\GetAdminPropertyStatsOverviewQuery;
use App\Domain\Property\Repository\AdminPropertyStatsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetAdminPropertyStatsOverviewHandlerTest extends TestCase
{
    public function testAggregatesDailyStatsAndTotals(): void
    {
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $yesterday = (new \DateTimeImmutable('today'))->modify('-1 day')->format('Y-m-d');

        $repository = $this->createMock(AdminPropertyStatsRepositoryInterface::class);
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

        $handler = new GetAdminPropertyStatsOverviewHandler($repository);
        $result = $handler(new GetAdminPropertyStatsOverviewQuery(
            period: 7,
            propertyType: 'apartment',
            cityId: 1,
        ));

        self::assertSame(7, $result['period']);
        self::assertSame('apartment', $result['propertyType']);
        self::assertSame(1, $result['cityId']);
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
        $repository = $this->createMock(AdminPropertyStatsRepositoryInterface::class);
        $repository->method('findAggregatedDailyStats')->willReturn([]);
        $repository->method('findAggregatedDailyFavorites')->willReturn([]);
        $repository->method('findAggregatedDailyReceivedMessages')->willReturn([]);
        $repository->method('findAggregatedDailyBookingInquiries')->willReturn([]);
        $repository->method('countProperties')->willReturn(0);

        $handler = new GetAdminPropertyStatsOverviewHandler($repository);
        $result = $handler(new GetAdminPropertyStatsOverviewQuery(
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

        $repository = $this->createMock(AdminPropertyStatsRepositoryInterface::class);
        $repository->method('findAggregatedDailyStats')->willReturn([
            ['date' => $today, 'views' => 4, 'phoneViews' => 1],
            ['date' => $yesterday, 'views' => 6, 'phoneViews' => 2],
        ]);
        $repository->method('findAggregatedDailyFavorites')->willReturn([]);
        $repository->method('findAggregatedDailyReceivedMessages')->willReturn([]);
        $repository->method('findAggregatedDailyBookingInquiries')->willReturn([]);
        $repository->method('countProperties')->willReturn(0);

        $handler = new GetAdminPropertyStatsOverviewHandler($repository);

        $todayResult = $handler(new GetAdminPropertyStatsOverviewQuery(period: 0, propertyType: null, cityId: null));
        self::assertSame(0, $todayResult['period']);
        self::assertCount(1, $todayResult['daily']);
        self::assertSame($today, $todayResult['daily'][0]['date']);
        self::assertSame(4, $todayResult['totals']['views']);

        $yesterdayResult = $handler(new GetAdminPropertyStatsOverviewQuery(period: -1, propertyType: null, cityId: null));
        self::assertSame(-1, $yesterdayResult['period']);
        self::assertCount(1, $yesterdayResult['daily']);
        self::assertSame($yesterday, $yesterdayResult['daily'][0]['date']);
        self::assertSame(6, $yesterdayResult['totals']['views']);
    }
}
