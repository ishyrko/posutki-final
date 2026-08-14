<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\ResidentialComplex;
use App\Domain\Property\Repository\ResidentialComplexRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;

class ResidentialComplexRepository extends ServiceEntityRepository implements ResidentialComplexRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResidentialComplex::class);
    }

    public function findById(int $id): ?ResidentialComplex
    {
        return $this->find($id);
    }

    public function findByCityIdAndOfficialName(int $cityId, string $officialName): ?ResidentialComplex
    {
        return $this->createQueryBuilder('c')
            ->where('c.cityId = :cityId')
            ->andWhere('c.officialName = :officialName')
            ->setParameter('cityId', $cityId)
            ->setParameter('officialName', trim($officialName))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByCityIdAndSlug(int $cityId, string $slug): ?ResidentialComplex
    {
        return $this->createQueryBuilder('c')
            ->where('c.cityId = :cityId')
            ->andWhere('c.slug = :slug')
            ->setParameter('cityId', $cityId)
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<ResidentialComplex>
     */
    public function findAllByCityId(int $cityId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.cityId = :cityId')
            ->andWhere('c.slug IS NOT NULL')
            ->setParameter('cityId', $cityId)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(ResidentialComplex $complex): void
    {
        try {
            $this->getEntityManager()->persist($complex);
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException) {
            $this->getEntityManager()->clear(ResidentialComplex::class);

            $existing = $this->findByCityIdAndOfficialName(
                $complex->getCityId(),
                $complex->getOfficialName(),
            );
            if ($existing === null) {
                throw new \RuntimeException('Failed to save residential complex after unique constraint violation');
            }
        }
    }
}
