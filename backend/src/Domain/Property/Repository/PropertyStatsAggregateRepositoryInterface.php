<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\ValueObject\PropertyStatsFilter;

interface PropertyStatsAggregateRepositoryInterface
{
    /**
     * @return array<int, array{date: string, views: int, phoneViews: int}>
     */
    public function findAggregatedDailyStats(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        PropertyStatsFilter $filter,
    ): array;

    /**
     * @return array<int, array{date: string, count: int}>
     */
    public function findAggregatedDailyFavorites(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        PropertyStatsFilter $filter,
    ): array;

    /**
     * Incoming messages to property owners (from buyers).
     *
     * @return array<int, array{date: string, count: int}>
     */
    public function findAggregatedDailyReceivedMessages(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        PropertyStatsFilter $filter,
    ): array;

    /**
     * @return array<int, array{date: string, count: int}>
     */
    public function findAggregatedDailyBookingInquiries(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        PropertyStatsFilter $filter,
    ): array;

    public function countProperties(PropertyStatsFilter $filter): int;
}
