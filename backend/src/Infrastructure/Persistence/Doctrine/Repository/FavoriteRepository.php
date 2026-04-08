<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Favorite\Entity\Favorite;
use App\Domain\Favorite\Repository\FavoriteRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FavoriteRepository extends ServiceEntityRepository implements FavoriteRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    public function save(Favorite $favorite): void
    {
        $this->getEntityManager()->persist($favorite);
        $this->getEntityManager()->flush();
    }

    public function delete(Favorite $favorite): void
    {
        $this->getEntityManager()->remove($favorite);
        $this->getEntityManager()->flush();
    }

    public function findByUserAndProperty(Id $userId, Id $propertyId): ?Favorite
    {
        return $this->createQueryBuilder('f')
            ->where('f.userId = :userId')
            ->andWhere('f.propertyId = :propertyId')
            ->setParameter('userId', $userId->getValue())
            ->setParameter('propertyId', $propertyId->getValue())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPropertyIdsByUser(Id $userId): array
    {
        $results = $this->createQueryBuilder('f')
            ->select('f.propertyId')
            ->where('f.userId = :userId')
            ->setParameter('userId', $userId->getValue())
            ->getQuery()
            ->getScalarResult();

        return array_map(
            fn($row) => $row['propertyId'] instanceof Id ? $row['propertyId']->getValue() : (int) $row['propertyId'],
            $results
        );
    }

    public function findByUser(Id $userId, int $page = 1, int $limit = 20): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.userId = :userId')
            ->setParameter('userId', $userId->getValue())
            ->orderBy('f.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByUser(Id $userId): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.userId = :userId')
            ->setParameter('userId', $userId->getValue())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByProperty(Id $propertyId): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.propertyId = :propertyId')
            ->setParameter('propertyId', $propertyId->getValue())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findDailyCountsByProperty(Id $propertyId, int $days): array
    {
        $endDate = (new \DateTimeImmutable('today'))->setTime(23, 59, 59);
        $startDate = $endDate->modify(sprintf('-%d days', max(0, $days - 1)))->setTime(0, 0);

        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT DATE(created_at) AS stat_date, COUNT(id) AS stat_count
             FROM favorites
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
