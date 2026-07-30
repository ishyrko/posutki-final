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
     *     period: ?int,
     *     dateFrom: ?string,
     *     dateTo: ?string,
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
        [$startDate, $endDate, $periodDays, $period, $dateFrom, $dateTo] = $this->resolvePeriod(
            $query->period,
            $query->dateFrom,
            $query->dateTo,
        );
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
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
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

    private const MAX_CUSTOM_PERIOD_DAYS = 366;

    /**
     * @return array{
     *     0: \DateTimeImmutable,
     *     1: \DateTimeImmutable,
     *     2: int,
     *     3: ?int,
     *     4: ?string,
     *     5: ?string
     * }
     */
    private function resolvePeriod(int $period, ?string $dateFrom, ?string $dateTo): array
    {
        $today = (new \DateTimeImmutable('today'))->setTime(0, 0);

        $customRange = $this->parseCustomDateRange($dateFrom, $dateTo, $today);
        if ($customRange !== null) {
            return [
                $customRange['start'],
                $customRange['end'],
                $customRange['days'],
                null,
                $customRange['dateFrom'],
                $customRange['dateTo'],
            ];
        }

        return match ($period) {
            0 => [$today, $today, 1, 0, null, null],
            -1 => [$today->modify('-1 day'), $today->modify('-1 day'), 1, -1, null, null],
            7, 30, 90 => [$today->modify(sprintf('-%d days', $period - 1)), $today, $period, $period, null, null],
            default => [$today->modify('-29 days'), $today, 30, 30, null, null],
        };
    }

    /**
     * @return array{start: \DateTimeImmutable, end: \DateTimeImmutable, days: int, dateFrom: string, dateTo: string}|null
     */
    private function parseCustomDateRange(?string $dateFrom, ?string $dateTo, \DateTimeImmutable $today): ?array
    {
        if ($dateFrom === null || $dateFrom === '' || $dateTo === null || $dateTo === '') {
            return null;
        }

        $start = \DateTimeImmutable::createFromFormat('!Y-m-d', $dateFrom);
        $end = \DateTimeImmutable::createFromFormat('!Y-m-d', $dateTo);
        if ($start === false || $end === false) {
            return null;
        }

        $errors = \DateTimeImmutable::getLastErrors();
        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }

        if ($start > $end || $end > $today) {
            return null;
        }

        $days = (int) $start->diff($end)->days + 1;
        if ($days > self::MAX_CUSTOM_PERIOD_DAYS) {
            return null;
        }

        return [
            'start' => $start,
            'end' => $end,
            'days' => $days,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }
}
