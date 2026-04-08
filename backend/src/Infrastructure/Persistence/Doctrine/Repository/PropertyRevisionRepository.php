<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\PropertyRevision;
use App\Domain\Property\Repository\PropertyRevisionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PropertyRevisionRepository extends ServiceEntityRepository implements PropertyRevisionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyRevision::class);
    }

    public function save(PropertyRevision $revision): void
    {
        $this->getEntityManager()->persist($revision);
        $this->getEntityManager()->flush();
    }

    public function findLatestByPropertyAndStatus(int $propertyId, string $status): ?PropertyRevision
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.property', 'p')
            ->andWhere('p.id = :propertyId')
            ->andWhere('r.status = :status')
            ->setParameter('propertyId', $propertyId)
            ->setParameter('status', $status)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findById(int $revisionId): ?PropertyRevision
    {
        return $this->find($revisionId);
    }
}
