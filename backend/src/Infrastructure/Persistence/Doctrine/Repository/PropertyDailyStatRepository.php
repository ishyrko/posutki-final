<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\PropertyDailyStat;
use App\Domain\Property\Repository\PropertyDailyStatRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PropertyDailyStatRepository extends ServiceEntityRepository implements PropertyDailyStatRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyDailyStat::class);
    }

    public function upsertView(int $propertyId, \DateTimeImmutable $date): void
    {
        $this->upsertColumn($propertyId, $date, 'views');
    }

    public function upsertPhoneView(int $propertyId, \DateTimeImmutable $date): void
    {
        $this->upsertColumn($propertyId, $date, 'phone_views');
    }

    public function findByPropertyAndPeriod(int $propertyId, int $days): array
    {
        $endDate = (new \DateTimeImmutable('today'))->setTime(0, 0);
        $startDate = $endDate->modify(sprintf('-%d days', max(0, $days - 1)));

        $rows = $this->createQueryBuilder('s')
            ->select('s.statDate AS statDate, s.views AS views, s.phoneViews AS phoneViews')
            ->where('s.propertyId = :propertyId')
            ->andWhere('s.statDate >= :startDate')
            ->andWhere('s.statDate <= :endDate')
            ->setParameter('propertyId', $propertyId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('s.statDate', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn(array $row): array => [
                'date' => $row['statDate'] instanceof \DateTimeInterface
                    ? $row['statDate']->format('Y-m-d')
                    : (string) $row['statDate'],
                'views' => (int) $row['views'],
                'phoneViews' => (int) $row['phoneViews'],
            ],
            $rows
        );
    }

    private function upsertColumn(int $propertyId, \DateTimeImmutable $date, string $column): void
    {
        $day = $date->setTime(0, 0)->format('Y-m-d');
        $connection = $this->getEntityManager()->getConnection();
        $viewsOnInsert = $column === 'views' ? 1 : 0;
        $phoneViewsOnInsert = $column === 'phone_views' ? 1 : 0;

        $connection->executeStatement(
            sprintf(
                'INSERT INTO property_daily_stats (property_id, stat_date, views, phone_views)
                 VALUES (:propertyId, :statDate, :viewsOnInsert, :phoneViewsOnInsert)
                 ON DUPLICATE KEY UPDATE %1$s = %1$s + 1',
                $column
            ),
            [
                'propertyId' => $propertyId,
                'statDate' => $day,
                'viewsOnInsert' => $viewsOnInsert,
                'phoneViewsOnInsert' => $phoneViewsOnInsert,
            ]
        );
    }
}
