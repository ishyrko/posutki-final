<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\RegionCatalogContent;
use App\Domain\Property\Repository\RegionCatalogContentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RegionCatalogContentRepository extends ServiceEntityRepository implements RegionCatalogContentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegionCatalogContent::class);
    }

    public function findById(int $id): ?RegionCatalogContent
    {
        return $this->find($id);
    }

    public function findByRegionId(int $regionId): ?RegionCatalogContent
    {
        return $this->createQueryBuilder('r')
            ->where('r.regionId = :regionId')
            ->setParameter('regionId', $regionId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(RegionCatalogContent $content): void
    {
        $this->getEntityManager()->persist($content);
        $this->getEntityManager()->flush();
    }
}
