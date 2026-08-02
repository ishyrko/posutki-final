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

    public function findByCityIdAndName(int $cityId, string $name): ?CityDistrict
    {
        return $this->createQueryBuilder('d')
            ->where('d.cityId = :cityId')
            ->andWhere('d.name = :name')
            ->setParameter('cityId', $cityId)
            ->setParameter('name', trim($name))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(CityDistrict $cityDistrict): void
    {
        try {
            $this->getEntityManager()->persist($cityDistrict);
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException) {
            $this->getEntityManager()->clear(CityDistrict::class);

            $existing = $this->findByCityIdAndName($cityDistrict->getCityId(), $cityDistrict->getName());
            if ($existing === null) {
                throw new \RuntimeException('Failed to save city district after unique constraint violation');
            }
        }
    }
}
