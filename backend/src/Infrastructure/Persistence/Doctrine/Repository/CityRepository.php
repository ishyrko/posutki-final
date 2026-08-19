<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\City;
use App\Domain\Property\Repository\CityRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CityRepository extends ServiceEntityRepository implements CityRepositoryInterface
{
    private const LISTING_SUGGESTED_MINSK_SLUG = 'minsk';

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

    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findListingSuggested(): array
    {
        /** @var list<City> $cities */
        $cities = $this->createQueryBuilder('c')
            ->leftJoin('c.regionDistrict', 'rd')->addSelect('rd')
            ->leftJoin('rd.region', 'r')->addSelect('r')
            ->where('c.isListingSuggested = :suggested')
            ->setParameter('suggested', true)
            ->getQuery()
            ->getResult();

        return self::sortListingSuggested($cities);
    }

    /**
     * @param list<City> $cities
     *
     * @return list<City>
     */
    private static function sortListingSuggested(array $cities): array
    {
        usort($cities, static function (City $a, City $b): int {
            $bucketCompare = self::listingSuggestedSortBucket($a) <=> self::listingSuggestedSortBucket($b);
            if ($bucketCompare !== 0) {
                return $bucketCompare;
            }

            return strcasecmp($a->getName(), $b->getName());
        });

        return $cities;
    }

    private static function listingSuggestedSortBucket(City $city): int
    {
        if ($city->getSlug() === self::LISTING_SUGGESTED_MINSK_SLUG) {
            return 0;
        }

        if ($city->isMain()) {
            return 1;
        }

        return 2;
    }
}
