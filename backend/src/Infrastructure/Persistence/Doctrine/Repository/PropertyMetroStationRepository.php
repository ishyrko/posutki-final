<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\PropertyMetroStation;
use App\Domain\Property\Repository\PropertyMetroStationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PropertyMetroStationRepository extends ServiceEntityRepository implements PropertyMetroStationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyMetroStation::class);
    }

    public function findByPropertyId(int $propertyId): array
    {
        return $this->createQueryBuilder('pms')
            ->where('pms.propertyId = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('pms.distanceKm', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByPropertyIds(array $propertyIds): array
    {
        if ($propertyIds === []) {
            return [];
        }

        return $this->createQueryBuilder('pms')
            ->where('pms.propertyId IN (:propertyIds)')
            ->setParameter('propertyIds', array_values($propertyIds))
            ->orderBy('pms.distanceKm', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function deleteByPropertyId(int $propertyId): void
    {
        $this->createQueryBuilder('pms')
            ->delete()
            ->where('pms.propertyId = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->getQuery()
            ->execute();
    }

    public function save(PropertyMetroStation $propertyMetroStation): void
    {
        $this->getEntityManager()->persist($propertyMetroStation);
        $this->getEntityManager()->flush();
    }
}
