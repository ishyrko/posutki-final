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
     *     propertiesCount: int,
     *     totals: array{views: int, phoneViews: int, favorites: int, messages: int, bookingInquiries: int},
     *     daily: array<int, array{date: string, views: int, phoneViews: int, favorites: int, messages: int, bookingInquiries: int}>
     * }
     */
    public function __invoke(GetAdminPropertyStatsOverviewQuery $query): array
    {
        $period = in_array($query->period, [7, 30, 90], true) ? $query->period : 30;
        $propertyType = $this->normalizePropertyType($query->propertyType);

        $endDate = (new \DateTimeImmutable('today'))->setTime(0, 0);
        $startDate = $endDate->modify(sprintf('-%d days', max(0, $period - 1)));

        $dailyPropertyStats = $this->adminPropertyStatsRepository->findAggregatedDailyStats(
            $startDate,
            $endDate,
            $propertyType,
            $query->cityId,
        );
        $dailyFavoritesStats = $this->adminPropertyStatsRepository->findAggregatedDailyFavorites(
            $startDate,
            $endDate,
            $propertyType,
            $query->cityId,
        );
        $dailyMessagesStats = $this->adminPropertyStatsRepository->findAggregatedDailyReceivedMessages(
            $startDate,
            $endDate,
            $propertyType,
            $query->cityId,
        );
        $dailyBookingInquiriesStats = $this->adminPropertyStatsRepository->findAggregatedDailyBookingInquiries(
            $startDate,
            $endDate,
            $propertyType,
            $query->cityId,
        );

        $dailyByDate = [];
        for ($day = 0; $day < $period; $day++) {
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
            'propertiesCount' => $this->adminPropertyStatsRepository->countProperties($propertyType, $query->cityId),
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
}
