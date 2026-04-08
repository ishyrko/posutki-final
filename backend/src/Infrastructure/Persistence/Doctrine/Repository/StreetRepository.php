<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\Street;
use App\Domain\Property\Repository\StreetRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StreetRepository extends ServiceEntityRepository implements StreetRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Street::class);
    }

    public function findById(int $id): ?Street
    {
        return $this->find($id);
    }

    public function findByCityId(int $cityId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.city = :cityId')
            ->setParameter('cityId', $cityId)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchByCityId(int $cityId, string $query): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.city = :cityId')
            ->andWhere('s.name LIKE :query')
            ->setParameter('cityId', $cityId)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('s.name', 'ASC')
            ->setMaxResults(30)
            ->getQuery()
            ->getResult();
    }
}
