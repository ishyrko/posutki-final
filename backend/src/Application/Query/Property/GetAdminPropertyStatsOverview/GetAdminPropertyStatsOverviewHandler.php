<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetAdminPropertyStatsOverview;

use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\AdminPropertyStatsRepositoryInterface;

final class GetAdminPropertyStatsOverviewHandler
{
    public function __construct(
        private readonly AdminPropertyStatsRepositoryInterface $adminPropertyStatsRepository,
    ) {
    }

    /**
     * @return array{
     *     period: int,
     *     propertyType: ?string,
     *     cityId: ?int,
     *     regionId: ?int,
     *     propertiesCount: int,
     *     totals: array{views: int, phoneViews: int, favorites: int, messages: int, bookingInquiries: int},
     *     daily: array<int, array{date: string, views: int, phoneViews: int, favorites: int, messages: int, bookingInquiries: int}>
     * }
     */
    public function __invoke(GetAdminPropertyStatsOverviewQuery $query): array
    {
        [$startDate, $endDate, $periodDays, $period] = $this->resolvePeriod($query->period);
        $propertyType = $this->normalizePropertyType($query->propertyType);

        $dailyPropertyStats = $this->adminPropertyStatsRepository->findAggregatedDailyStats(
            $startDate,
            $endDate,
            $propertyType,
            $query->cityId,
            $query->regionId,
        );
        $dailyFavoritesStats = $this->adminPropertyStatsRepository->findAggregatedDailyFavorites(
            $startDate,
            $endDate,
            $propertyType,
            $query->cityId,
            $query->regionId,
        );
        $dailyMessagesStats = $this->adminPropertyStatsRepository->findAggregatedDailyReceivedMessages(
            $startDate,
            $endDate,
            $propertyType,
            $query->cityId,
            $query->regionId,
        );
        $dailyBookingInquiriesStats = $this->adminPropertyStatsRepository->findAggregatedDailyBookingInquiries(
            $startDate,
            $endDate,
            $propertyType,
            $query->cityId,
            $query->regionId,
        );

        $dailyByDate = [];
        for ($day = 0; $day < $periodDays; $day++) {
            $date = $startDate->modify(sprintf('+%d days', $day))->format('Y-m-d');
            $dailyByDate[$date] = [
                'date' => $date,
                'views' => 0,
                'phoneViews' => 0,
                'favorites' => 0,
                'messages' => 0,
                'bookingInquiries' => 0,
            ];
        }

        foreach ($dailyPropertyStats as $row) {
            $date = $row['date'];
            if (!isset($dailyByDate[$date])) {
                continue;
            }

            $dailyByDate[$date]['views'] = $row['views'];
            $dailyByDate[$date]['phoneViews'] = $row['phoneViews'];
        }

        foreach ($dailyFavoritesStats as $row) {
            $date = $row['date'];
            if (!isset($dailyByDate[$date])) {
                continue;
            }

            $dailyByDate[$date]['favorites'] = $row['count'];
        }

        foreach ($dailyMessagesStats as $row) {
            $date = $row['date'];
            if (!isset($dailyByDate[$date])) {
                continue;
            }

            $dailyByDate[$date]['messages'] = $row['count'];
        }

        foreach ($dailyBookingInquiriesStats as $row) {
            $date = $row['date'];
            if (!isset($dailyByDate[$date])) {
                continue;
            }

            $dailyByDate[$date]['bookingInquiries'] = $row['count'];
        }

        $daily = array_values($dailyByDate);

        return [
            'period' => $period,
            'propertyType' => $propertyType,
            'cityId' => $query->cityId,
            'regionId' => $query->regionId,
            'propertiesCount' => $this->adminPropertyStatsRepository->countProperties($propertyType, $query->cityId, $query->regionId),
            'totals' => [
                'views' => array_sum(array_column($daily, 'views')),
                'phoneViews' => array_sum(array_column($daily, 'phoneViews')),
                'favorites' => array_sum(array_column($daily, 'favorites')),
                'messages' => array_sum(array_column($daily, 'messages')),
                'bookingInquiries' => array_sum(array_column($daily, 'bookingInquiries')),
            ],
            'daily' => $daily,
        ];
    }

    private function normalizePropertyType(?string $propertyType): ?string
    {
        if ($propertyType === null || $propertyType === '') {
            return null;
        }

        return match ($propertyType) {
            PropertyType::Apartment->value, PropertyType::House->value => $propertyType,
            default => null,
        };
    }

    /**
     * @return array{
     *     0: \DateTimeImmutable,
     *     1: \DateTimeImmutable,
     *     2: int,
     *     3: int
     * }
     */
    private function resolvePeriod(int $period): array
    {
        $today = (new \DateTimeImmutable('today'))->setTime(0, 0);

        return match ($period) {
            0 => [$today, $today, 1, 0],
            -1 => [$today->modify('-1 day'), $today->modify('-1 day'), 1, -1],
            7, 30, 90 => [$today->modify(sprintf('-%d days', $period - 1)), $today, $period, $period],
            default => [$today->modify('-29 days'), $today, 30, 30],
        };
    }
}
