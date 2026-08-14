<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\CityMicrodistrict;
use App\Domain\Property\Repository\CityMicrodistrictRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;

class CityMicrodistrictRepository extends ServiceEntityRepository implements CityMicrodistrictRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CityMicrodistrict::class);
    }

    public function findById(int $id): ?CityMicrodistrict
    {
        return $this->find($id);
    }

    public function findByCityIdAndOfficialName(int $cityId, string $officialName): ?CityMicrodistrict
    {
        return $this->createQueryBuilder('m')
            ->where('m.cityId = :cityId')
            ->andWhere('m.officialName = :officialName')
            ->setParameter('cityId', $cityId)
            ->setParameter('officialName', trim($officialName))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByCityIdAndSlug(int $cityId, string $slug): ?CityMicrodistrict
    {
        return $this->createQueryBuilder('m')
            ->where('m.cityId = :cityId')
            ->andWhere('m.slug = :slug')
            ->setParameter('cityId', $cityId)
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<CityMicrodistrict>
     */
    public function findAllByCityId(int $cityId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.cityId = :cityId')
            ->andWhere('m.slug IS NOT NULL')
            ->setParameter('cityId', $cityId)
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(CityMicrodistrict $microdistrict): void
    {
        try {
            $this->getEntityManager()->persist($microdistrict);
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException) {
            $this->getEntityManager()->clear(CityMicrodistrict::class);

            $existing = $this->findByCityIdAndOfficialName(
                $microdistrict->getCityId(),
                $microdistrict->getOfficialName(),
            );
            if ($existing === null) {
                throw new \RuntimeException('Failed to save city microdistrict after unique constraint violation');
            }
        }
    }
}
