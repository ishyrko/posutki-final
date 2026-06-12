<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\PropertyAvailabilityBlock;
use App\Domain\Property\Repository\PropertyAvailabilityBlockRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PropertyAvailabilityBlockRepository extends ServiceEntityRepository implements PropertyAvailabilityBlockRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyAvailabilityBlock::class);
    }

    public function save(PropertyAvailabilityBlock $block): void
    {
        $this->getEntityManager()->persist($block);
        $this->getEntityManager()->flush();
    }

    public function delete(PropertyAvailabilityBlock $block): void
    {
        $this->getEntityManager()->remove($block);
        $this->getEntityManager()->flush();
    }

    public function findById(Id $id): ?PropertyAvailabilityBlock
    {
        return $this->find($id->getValue());
    }

    public function findByPropertyId(Id $propertyId): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.propertyId = :propertyId')
            ->setParameter('propertyId', $propertyId->getValue())
            ->orderBy('b.startDate', 'ASC')
            ->addOrderBy('b.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
