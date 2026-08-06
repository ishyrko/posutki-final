<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\Landmark;
use App\Domain\Property\Repository\LandmarkRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LandmarkRepository extends ServiceEntityRepository implements LandmarkRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Landmark::class);
    }

    public function findActiveByCityId(int $cityId): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.cityId = :cityId')
            ->andWhere('l.isActive = :active')
            ->setParameter('cityId', $cityId)
            ->setParameter('active', true)
            ->orderBy('l.sortOrder', 'ASC')
            ->addOrderBy('l.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCityIdAndSlug(int $cityId, string $slug): ?Landmark
    {
        return $this->createQueryBuilder('l')
            ->where('l.cityId = :cityId')
            ->andWhere('l.slug = :slug')
            ->andWhere('l.isActive = :active')
            ->setParameter('cityId', $cityId)
            ->setParameter('slug', $slug)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAnyByCityIdAndSlug(int $cityId, string $slug): ?Landmark
    {
        return $this->createQueryBuilder('l')
            ->where('l.cityId = :cityId')
            ->andWhere('l.slug = :slug')
            ->setParameter('cityId', $cityId)
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findById(int $id): ?Landmark
    {
        return $this->find($id);
    }

    public function findActiveByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('l')
            ->where('l.id IN (:ids)')
            ->andWhere('l.isActive = :active')
            ->setParameter('ids', array_values($ids))
            ->setParameter('active', true)
            ->orderBy('l.sortOrder', 'ASC')
            ->addOrderBy('l.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Landmark $landmark): void
    {
        $this->getEntityManager()->persist($landmark);
        $this->getEntityManager()->flush();
    }

    public function delete(Landmark $landmark): void
    {
        $this->getEntityManager()->remove($landmark);
        $this->getEntityManager()->flush();
    }
}
