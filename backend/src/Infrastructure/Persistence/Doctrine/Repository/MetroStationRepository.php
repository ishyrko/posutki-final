<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\MetroStation;
use App\Domain\Property\Repository\MetroStationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MetroStationRepository extends ServiceEntityRepository implements MetroStationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetroStation::class);
    }

    public function findById(int $id): ?MetroStation
    {
        return $this->find($id);
    }

    public function findByCityId(int $cityId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.cityId = :cityId')
            ->setParameter('cityId', $cityId)
            ->orderBy('m.line', 'ASC')
            ->addOrderBy('m.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('m')
            ->where('m.id IN (:ids)')
            ->setParameter('ids', array_values($ids))
            ->orderBy('m.line', 'ASC')
            ->addOrderBy('m.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
