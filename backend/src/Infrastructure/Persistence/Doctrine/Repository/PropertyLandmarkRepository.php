<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\PropertyLandmark;
use App\Domain\Property\Repository\PropertyLandmarkRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PropertyLandmarkRepository extends ServiceEntityRepository implements PropertyLandmarkRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyLandmark::class);
    }

    public function findByPropertyId(int $propertyId): array
    {
        return $this->createQueryBuilder('pl')
            ->where('pl.propertyId = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('pl.distanceKm', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByPropertyIds(array $propertyIds): array
    {
        if ($propertyIds === []) {
            return [];
        }

        return $this->createQueryBuilder('pl')
            ->where('pl.propertyId IN (:propertyIds)')
            ->setParameter('propertyIds', array_values($propertyIds))
            ->orderBy('pl.distanceKm', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function deleteByPropertyId(int $propertyId): void
    {
        $this->createQueryBuilder('pl')
            ->delete()
            ->where('pl.propertyId = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->getQuery()
            ->execute();
    }

    public function save(PropertyLandmark $propertyLandmark): void
    {
        $this->getEntityManager()->persist($propertyLandmark);
        $this->getEntityManager()->flush();
    }
}
