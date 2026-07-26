<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

interface AdminPropertyStatsRepositoryInterface
{
    /**
     * @return array<int, array{date: string, views: int, phoneViews: int}>
     */
    public function findAggregatedDailyStats(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ?string $propertyType,
        ?int $cityId,
    ): array;

    /**
     * @return array<int, array{date: string, count: int}>
     */
    public function findAggregatedDailyFavorites(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ?string $propertyType,
        ?int $cityId,
    ): array;

    /**
     * Incoming messages to property owners (from buyers).
     *
     * @return array<int, array{date: string, count: int}>
     */
    public function findAggregatedDailyReceivedMessages(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ?string $propertyType,
        ?int $cityId,
    ): array;

    /**
     * @return array<int, array{date: string, count: int}>
     */
    public function findAggregatedDailyBookingInquiries(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ?string $propertyType,
        ?int $cityId,
    ): array;

    public function countProperties(?string $propertyType, ?int $cityId): int;
}
