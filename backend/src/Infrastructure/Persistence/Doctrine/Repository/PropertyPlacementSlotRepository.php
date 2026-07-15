<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\PropertyPlacementSlot;
use App\Domain\Property\Repository\PropertyPlacementSlotRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PropertyPlacementSlot>
 */
class PropertyPlacementSlotRepository extends ServiceEntityRepository implements PropertyPlacementSlotRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyPlacementSlot::class);
    }

    public function save(PropertyPlacementSlot $slot): void
    {
        $this->getEntityManager()->persist($slot);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id): ?PropertyPlacementSlot
    {
        return $this->find($id);
    }

    public function findActiveByCityId(int $cityId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.cityId = :cityId')
            ->andWhere('s.isActive = true')
            ->setParameter('cityId', $cityId)
            ->orderBy('s.sortOrder', 'ASC')
            ->addOrderBy('s.rankFrom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.cityId', 'ASC')
            ->addOrderBy('s.sortOrder', 'ASC')
            ->addOrderBy('s.rankFrom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
