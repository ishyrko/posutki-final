<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\CityDistrict;
use App\Domain\Property\Repository\CityDistrictRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;

class CityDistrictRepository extends ServiceEntityRepository implements CityDistrictRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CityDistrict::class);
    }

    public function findById(int $id): ?CityDistrict
    {
        return $this->find($id);
    }

    public function findByCityIdAndOfficialName(int $cityId, string $officialName): ?CityDistrict
    {
        return $this->createQueryBuilder('d')
            ->where('d.cityId = :cityId')
            ->andWhere('d.officialName = :officialName')
            ->setParameter('cityId', $cityId)
            ->setParameter('officialName', trim($officialName))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByCityIdAndName(int $cityId, string $name): ?CityDistrict
    {
        return $this->findByCityIdAndOfficialName($cityId, $name);
    }

    public function findByCityIdAndSlug(int $cityId, string $slug): ?CityDistrict
    {
        return $this->createQueryBuilder('d')
            ->where('d.cityId = :cityId')
            ->andWhere('d.slug = :slug')
            ->setParameter('cityId', $cityId)
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<CityDistrict>
     */
    public function findAllByCityId(int $cityId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.cityId = :cityId')
            ->andWhere('d.slug IS NOT NULL')
            ->setParameter('cityId', $cityId)
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<CityDistrict>
     */
    public function findAllWithoutSlug(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.slug IS NULL')
            ->orderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(CityDistrict $cityDistrict): void
    {
        try {
            $this->getEntityManager()->persist($cityDistrict);
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException) {
            $this->getEntityManager()->clear(CityDistrict::class);

            $existing = $this->findByCityIdAndOfficialName(
                $cityDistrict->getCityId(),
                $cityDistrict->getOfficialName(),
            );
            if ($existing === null) {
                throw new \RuntimeException('Failed to save city district after unique constraint violation');
            }
        }
    }
}
