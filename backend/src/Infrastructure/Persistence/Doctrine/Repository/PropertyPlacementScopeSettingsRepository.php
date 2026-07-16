<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\PropertyPlacementScopeSettings;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\PropertyPlacementScopeSettingsRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PropertyPlacementScopeSettings>
 */
class PropertyPlacementScopeSettingsRepository extends ServiceEntityRepository implements PropertyPlacementScopeSettingsRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyPlacementScopeSettings::class);
    }

    public function save(PropertyPlacementScopeSettings $settings): void
    {
        $this->getEntityManager()->persist($settings);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id): ?PropertyPlacementScopeSettings
    {
        return $this->find($id);
    }

    public function findActiveByCityId(int $cityId): ?PropertyPlacementScopeSettings
    {
        return $this->createQueryBuilder('s')
            ->where('s.cityId = :cityId')
            ->andWhere('s.propertyType = :propertyType')
            ->andWhere('s.isActive = true')
            ->setParameter('cityId', $cityId)
            ->setParameter('propertyType', PropertyType::Apartment->value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByRegionId(int $regionId): ?PropertyPlacementScopeSettings
    {
        return $this->createQueryBuilder('s')
            ->where('s.regionId = :regionId')
            ->andWhere('s.propertyType = :propertyType')
            ->andWhere('s.isActive = true')
            ->setParameter('regionId', $regionId)
            ->setParameter('propertyType', PropertyType::House->value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findConfiguredCityIds(string $propertyType): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select('s.cityId')
            ->where('s.propertyType = :propertyType')
            ->andWhere('s.cityId IS NOT NULL')
            ->setParameter('propertyType', $propertyType)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $rows);
    }

    public function findConfiguredRegionIds(string $propertyType): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select('s.regionId')
            ->where('s.propertyType = :propertyType')
            ->andWhere('s.regionId IS NOT NULL')
            ->setParameter('propertyType', $propertyType)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $rows);
    }
}
