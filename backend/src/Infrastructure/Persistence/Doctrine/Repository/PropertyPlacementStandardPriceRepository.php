<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\PropertyPlacementStandardPrice;
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
            ->andWhere('p.isActive = true')
            ->setParameter('cityId', $cityId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
