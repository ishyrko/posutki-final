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

    public function findByVisitorAndProperty(string $visitorId, Id $propertyId): ?Favorite
    {
        $visitorId = Favorite::normalizeVisitorId($visitorId);

        return $this->createQueryBuilder('f')
            ->where('f.visitorId = :visitorId')
            ->andWhere('f.propertyId = :propertyId')
            ->setParameter('visitorId', $visitorId)
            ->setParameter('propertyId', $propertyId->getValue())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteByVisitorAndProperty(string $visitorId, Id $propertyId): void
    {
        $favorite = $this->findByVisitorAndProperty($visitorId, $propertyId);
        if ($favorite === null) {
            return;
        }

        $this->delete($favorite);
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
}
