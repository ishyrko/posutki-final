<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\PropertyPlacementLevelPrice;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\PropertyPlacementLevelPriceRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PropertyPlacementLevelPrice>
 */
class PropertyPlacementLevelPriceRepository extends ServiceEntityRepository implements PropertyPlacementLevelPriceRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyPlacementLevelPrice::class);
    }

    public function save(PropertyPlacementLevelPrice $levelPrice): void
    {
        $this->getEntityManager()->persist($levelPrice);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id): ?PropertyPlacementLevelPrice
    {
        return $this->find($id);
    }

    public function findActiveByCityId(int $cityId): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.cityId = :cityId')
            ->andWhere('l.propertyType = :propertyType')
            ->andWhere('l.isActive = true')
            ->setParameter('cityId', $cityId)
            ->setParameter('propertyType', PropertyType::Apartment->value)
            ->orderBy('l.level', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByRegionId(int $regionId): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.regionId = :regionId')
            ->andWhere('l.propertyType = :propertyType')
            ->andWhere('l.isActive = true')
            ->setParameter('regionId', $regionId)
            ->setParameter('propertyType', PropertyType::House->value)
            ->orderBy('l.level', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.cityId', 'ASC')
            ->addOrderBy('l.regionId', 'ASC')
            ->addOrderBy('l.level', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
