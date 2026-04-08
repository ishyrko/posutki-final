<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\RegionDistrict;
use App\Domain\Property\Repository\RegionDistrictRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RegionDistrictRepository extends ServiceEntityRepository implements RegionDistrictRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegionDistrict::class);
    }

    public function findById(int $id): ?RegionDistrict
    {
        return $this->find($id);
    }

    public function findByRegionId(int $regionId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.region = :regionId')
            ->setParameter('regionId', $regionId)
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
