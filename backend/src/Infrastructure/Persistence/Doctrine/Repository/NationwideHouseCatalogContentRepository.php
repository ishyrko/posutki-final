<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\NationwideHouseCatalogContent;
use App\Domain\Property\Repository\NationwideHouseCatalogContentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NationwideHouseCatalogContentRepository extends ServiceEntityRepository implements NationwideHouseCatalogContentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NationwideHouseCatalogContent::class);
    }

    public function findSingle(): ?NationwideHouseCatalogContent
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(NationwideHouseCatalogContent $content): void
    {
        $this->getEntityManager()->persist($content);
        $this->getEntityManager()->flush();
    }
}
