<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Repository\AdminPropertyStatsRepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

final class AdminPropertyStatsRepository implements AdminPropertyStatsRepositoryInterface
{
    public function __construct(
        private readonly ManagerRegistry $registry,
    ) {
    }

    public function findAggregatedDailyStats(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ?string $propertyType,
        ?int $cityId,
        ?int $regionId = null,
    ): array {
        [$filterSql, $joinSql, $filterParams] = $this->buildPropertyFilterSql($propertyType, $cityId, $regionId);

        $rows = $this->connection()->executeQuery(
            'SELECT s.stat_date AS stat_date,
                    SUM(s.views) AS views,
                    SUM(s.phone_views) AS phone_views
             FROM property_daily_stats s
             INNER JOIN properties p ON p.id = s.property_id'
            . $joinSql .
            ' WHERE s.stat_date >= :startDate
               AND s.stat_date <= :endDate'
            . $filterSql .
            ' GROUP BY s.stat_date
             ORDER BY s.stat_date ASC',
            array_merge(
                [
                    'startDate' => $startDate->format('Y-m-d'),
                    'endDate' => $endDate->format('Y-m-d'),
                ],
                $filterParams,
            ),
        )->fetchAllAssociative();

        return array_map(
            static fn(array $row): array => [
                'date' => (string) $row['stat_date'],
                'views' => (int) $row['views'],
                'phoneViews' => (int) $row['phone_views'],
            ],
            $rows,
        );
    }

    public function findAggregatedDailyFavorites(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ?string $propertyType,
        ?int $cityId,
        ?int $regionId = null,
    ): array {
        [$filterSql, $joinSql, $filterParams] = $this->buildPropertyFilterSql($propertyType, $cityId, $regionId);

        $rows = $this->connection()->executeQuery(
            'SELECT DATE(f.created_at) AS stat_date,
                    COUNT(f.id) AS stat_count
             FROM favorites f
             INNER JOIN properties p ON p.id = f.property_id'
            . $joinSql .
            ' WHERE f.created_at >= :startDate
               AND f.created_at <= :endDate'
            . $filterSql .
            ' GROUP BY DATE(f.created_at)
             ORDER BY DATE(f.created_at) ASC',
            array_merge(
                [
                    'startDate' => $startDate->setTime(0, 0)->format('Y-m-d H:i:s'),
                    'endDate' => $endDate->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
                ],
                $filterParams,
            ),
        )->fetchAllAssociative();

        return array_map(
            static fn(array $row): array => [
                'date' => (string) $row['stat_date'],
                'count' => (int) $row['stat_count'],
            ],
            $rows,
        );
    }

    public function findAggregatedDailyReceivedMessages(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ?string $propertyType,
        ?int $cityId,
        ?int $regionId = null,
    ): array {
        [$filterSql, $joinSql, $filterParams] = $this->buildPropertyFilterSql($propertyType, $cityId, $regionId);

        $rows = $this->connection()->executeQuery(
            'SELECT DATE(m.created_at) AS stat_date,
                    COUNT(m.id) AS stat_count
             FROM messages m
             INNER JOIN conversations c ON c.id = m.conversation_id
             INNER JOIN properties p ON p.id = c.property_id'
            . $joinSql .
            ' WHERE m.created_at >= :startDate
               AND m.created_at <= :endDate
               AND m.sender_id != c.seller_id'
            . $filterSql .
            ' GROUP BY DATE(m.created_at)
             ORDER BY DATE(m.created_at) ASC',
            array_merge(
                [
                    'startDate' => $startDate->setTime(0, 0)->format('Y-m-d H:i:s'),
                    'endDate' => $endDate->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
                ],
                $filterParams,
            ),
        )->fetchAllAssociative();

        return array_map(
            static fn(array $row): array => [
                'date' => (string) $row['stat_date'],
                'count' => (int) $row['stat_count'],
            ],
            $rows,
        );
    }

    public function findAggregatedDailyBookingInquiries(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ?string $propertyType,
        ?int $cityId,
        ?int $regionId = null,
    ): array {
        [$filterSql, $joinSql, $filterParams] = $this->buildPropertyFilterSql($propertyType, $cityId, $regionId);

        $rows = $this->connection()->executeQuery(
            'SELECT DATE(b.created_at) AS stat_date,
                    COUNT(b.id) AS stat_count
             FROM booking_inquiries b
             INNER JOIN properties p ON p.id = b.property_id'
            . $joinSql .
            ' WHERE b.created_at >= :startDate
               AND b.created_at <= :endDate'
            . $filterSql .
            ' GROUP BY DATE(b.created_at)
             ORDER BY DATE(b.created_at) ASC',
            array_merge(
                [
                    'startDate' => $startDate->setTime(0, 0)->format('Y-m-d H:i:s'),
                    'endDate' => $endDate->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
                ],
                $filterParams,
            ),
        )->fetchAllAssociative();

        return array_map(
            static fn(array $row): array => [
                'date' => (string) $row['stat_date'],
                'count' => (int) $row['stat_count'],
            ],
            $rows,
        );
    }

    public function countProperties(?string $propertyType, ?int $cityId, ?int $regionId = null): int
    {
        [$filterSql, $joinSql, $filterParams] = $this->buildPropertyFilterSql($propertyType, $cityId, $regionId, '');

        return (int) $this->connection()->executeQuery(
            'SELECT COUNT(p.id) FROM properties p' . $joinSql . ' WHERE 1 = 1' . $filterSql,
            $filterParams,
        )->fetchOne();
    }

    /**
     * @return array{0: string, 1: string, 2: array<string, mixed>}
     */
    private function buildPropertyFilterSql(
        ?string $propertyType,
        ?int $cityId,
        ?int $regionId,
        string $alias = 'p',
    ): array {
        $tableAlias = $alias !== '' ? $alias : 'p';
        $conditions = [];
        $params = [];
        $joinSql = '';

        if ($propertyType !== null) {
            $conditions[] = sprintf('%s.type = :propertyType', $tableAlias);
            $params['propertyType'] = $propertyType;
        }

        if ($cityId !== null) {
            $conditions[] = sprintf('%s.city_id = :cityId', $tableAlias);
            $params['cityId'] = $cityId;
        } elseif ($regionId !== null) {
            $joinSql = sprintf(
                ' INNER JOIN cities _stats_city ON _stats_city.id = %s.city_id'
                . ' INNER JOIN region_districts _stats_rd ON _stats_rd.id = _stats_city.region_district_id'
                . ' INNER JOIN regions _stats_region ON _stats_region.id = _stats_rd.region_id',
                $tableAlias,
            );
            $conditions[] = '_stats_region.id = :regionId';
            $params['regionId'] = $regionId;
        }

        $filterSql = $conditions === [] ? '' : ' AND ' . implode(' AND ', $conditions);

        return [$filterSql, $joinSql, $params];
    }

    private function connection(): Connection
    {
        return $this->registry->getConnection();
    }
}
