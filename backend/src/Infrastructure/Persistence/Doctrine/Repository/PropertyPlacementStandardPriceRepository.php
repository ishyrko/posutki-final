<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\PropertyPlacementStandardPrice;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\PropertyPlacementStandardPriceRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PropertyPlacementStandardPrice>
 */
class PropertyPlacementStandardPriceRepository extends ServiceEntityRepository implements PropertyPlacementStandardPriceRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyPlacementStandardPrice::class);
    }

    public function save(PropertyPlacementStandardPrice $price): void
    {
        $this->getEntityManager()->persist($price);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id): ?PropertyPlacementStandardPrice
    {
        return $this->find($id);
    }

    public function findActiveByCityId(int $cityId): ?PropertyPlacementStandardPrice
    {
        return $this->createQueryBuilder('p')
            ->where('p.cityId = :cityId')
            ->andWhere('p.propertyType = :propertyType')
            ->andWhere('p.isActive = true')
            ->setParameter('cityId', $cityId)
            ->setParameter('propertyType', PropertyType::Apartment->value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByRegionId(int $regionId): ?PropertyPlacementStandardPrice
    {
        return $this->createQueryBuilder('p')
            ->where('p.regionId = :regionId')
            ->andWhere('p.propertyType = :propertyType')
            ->andWhere('p.isActive = true')
            ->setParameter('regionId', $regionId)
            ->setParameter('propertyType', PropertyType::House->value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findConfiguredCityIds(string $propertyType): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.cityId')
            ->where('p.propertyType = :propertyType')
            ->andWhere('p.cityId IS NOT NULL')
            ->setParameter('propertyType', $propertyType)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $rows);
    }

    public function findConfiguredRegionIds(string $propertyType): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.regionId')
            ->where('p.propertyType = :propertyType')
            ->andWhere('p.regionId IS NOT NULL')
            ->setParameter('propertyType', $propertyType)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $rows);
    }
}
