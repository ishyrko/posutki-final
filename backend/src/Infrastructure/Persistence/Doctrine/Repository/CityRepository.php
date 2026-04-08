<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\City;
use App\Domain\Property\Repository\CityRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CityRepository extends ServiceEntityRepository implements CityRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, City::class);
    }

    public function findById(int $id): ?City
    {
        return $this->find($id);
    }

    public function findBySlug(string $slug): ?City
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findByDistrictId(int $districtId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.regionDistrict = :districtId')
            ->setParameter('districtId', $districtId)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchByName(string $query, ?int $districtId = null, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.name LIKE :query')
            ->setParameter('query', '%' . $query . '%');

        if ($districtId !== null) {
            $qb->andWhere('c.regionDistrict = :districtId')
                ->setParameter('districtId', $districtId);
        }

        return $qb->orderBy('c.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
