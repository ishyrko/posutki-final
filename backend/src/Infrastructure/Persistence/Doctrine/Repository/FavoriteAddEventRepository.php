<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Favorite\Entity\FavoriteAddEvent;
use App\Domain\Favorite\Repository\FavoriteAddEventRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FavoriteAddEventRepository extends ServiceEntityRepository implements FavoriteAddEventRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FavoriteAddEvent::class);
    }

    public function record(Id $propertyId): void
    {
        $this->getEntityManager()->persist(FavoriteAddEvent::create($propertyId));
        $this->getEntityManager()->flush();
    }

    public function findDailyCountsByProperty(Id $propertyId, int $days): array
    {
        $endDate = (new \DateTimeImmutable('today'))->setTime(23, 59, 59);
        $startDate = $endDate->modify(sprintf('-%d days', max(0, $days - 1)))->setTime(0, 0);

        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT DATE(created_at) AS stat_date, COUNT(id) AS stat_count
             FROM favorite_add_events
             WHERE property_id = :propertyId
               AND created_at >= :startDate
               AND created_at <= :endDate
             GROUP BY DATE(created_at)
             ORDER BY DATE(created_at) ASC',
            [
                'propertyId' => $propertyId->getValue(),
                'startDate' => $startDate->format('Y-m-d H:i:s'),
                'endDate' => $endDate->format('Y-m-d H:i:s'),
            ]
        )->fetchAllAssociative();

        return array_map(
            static fn(array $row): array => [
                'date' => (string) $row['stat_date'],
                'count' => (int) $row['stat_count'],
            ],
            $rows
        );
    }
}
