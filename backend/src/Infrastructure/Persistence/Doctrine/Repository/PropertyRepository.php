<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\PropertyMetroStation;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class PropertyRepository extends ServiceEntityRepository implements PropertyRepositoryInterface
{
    private const ROOM_COUNT_PROPERTY_TYPES = [
        PropertyType::Apartment->value,
        PropertyType::House->value,
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    public function save(Property $property): void
    {
        $this->getEntityManager()->persist($property);
        $this->getEntityManager()->flush();
    }

    public function findById(Id $id): ?Property
    {
        return $this->find($id->getValue());
    }

    public function findPublished(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', 'published');

        $this->applyFilters($qb, $filters);

        $todayStart = new \DateTimeImmutable('today');
        $qb->addSelect('(CASE WHEN p.boostedAt >= :boostTodayStart THEN 1 ELSE 0 END) AS HIDDEN boostScore')
            ->setParameter('boostTodayStart', $todayStart);

        $qb->orderBy('boostScore', 'DESC')
            ->addOrderBy('p.boostedAt', 'DESC');

        // Sorting (tertiary after boost score / boostedAt)
        $sortBy = $filters['sortBy'] ?? 'createdAt';
        $sortOrder = $filters['sortOrder'] ?? 'DESC';
        if (!in_array($sortOrder, ['ASC', 'DESC'], true)) {
            $sortOrder = 'DESC';
        }

        // API uses sortBy=price; DB stores comparable totals in price_byn / price_per_meter_byn
        $priceType = $filters['priceType'] ?? 'total';
        $sortField = match ($sortBy) {
            'area' => 'area',
            'price' => $priceType === 'perMeter' ? 'pricePerMeterByn' : 'priceByn',
            'createdAt' => 'createdAt',
            default => 'createdAt',
        };

        $qb->addOrderBy('p.' . $sortField, $sortOrder);

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function findPublishedOlderThan(\DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.createdAt < :date')
            ->setParameter('status', 'published')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    public function count(array $filters = []): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->setParameter('status', 'published');

        $this->applyFilters($qb, $filters);

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    private function applyFilters($qb, array $filters): void
    {
        $this->applyPropertyTypeAndRoomsFilters($qb, $filters);

        if (isset($filters['dealType'])) {
            $qb->andWhere('p.dealType = :dealType')
                ->setParameter('dealType', $filters['dealType']);
        }

        if (isset($filters['cityId'])) {
            $qb->andWhere('p.cityId = :cityId')
                ->setParameter('cityId', $filters['cityId']);
        }

        if (isset($filters['citySlug']) || isset($filters['regionSlug'])) {
            $qb->innerJoin(City::class, 'c', 'WITH', 'c.id = p.cityId');
        }

        if (isset($filters['citySlug'])) {
            $qb->andWhere('c.slug = :citySlug')
                ->setParameter('citySlug', $filters['citySlug']);
        }

        if (isset($filters['regionSlug'])) {
            $qb->innerJoin('c.regionDistrict', 'rd')
                ->innerJoin('rd.region', 'r')
                ->andWhere('r.slug = :regionSlug')
                ->setParameter('regionSlug', $filters['regionSlug']);
        }

        if (
            isset($filters['excludeCitySlugs'])
            && \is_array($filters['excludeCitySlugs'])
            && $filters['excludeCitySlugs'] !== []
        ) {
            $qb->andWhere('c.slug NOT IN (:excludeCitySlugs)')
                ->setParameter('excludeCitySlugs', array_values($filters['excludeCitySlugs']), ArrayParameterType::STRING);
        }

        $priceType = $filters['priceType'] ?? 'total';
        $priceField = $priceType === 'perMeter' ? 'p.pricePerMeterByn' : 'p.priceByn';

        if (isset($filters['minPriceByn'])) {
            $qb->andWhere("{$priceField} >= :minPriceByn")
                ->setParameter('minPriceByn', $filters['minPriceByn']);
        }

        if (isset($filters['maxPriceByn'])) {
            $qb->andWhere("{$priceField} <= :maxPriceByn")
                ->setParameter('maxPriceByn', $filters['maxPriceByn']);
        }

        if (isset($filters['minArea'])) {
            $qb->andWhere('p.area >= :minArea')
                ->setParameter('minArea', $filters['minArea']);
        }

        if (isset($filters['maxArea'])) {
            $qb->andWhere('p.area <= :maxArea')
                ->setParameter('maxArea', $filters['maxArea']);
        }

        if (!empty($filters['nearMetro'])) {
            $qb->andWhere('p.nearMetro = :nearMetro')
                ->setParameter('nearMetro', true);
        }

        if (isset($filters['metroStationId'])) {
            $qb->innerJoin(PropertyMetroStation::class, 'pms', 'WITH', 'pms.propertyId = p.id')
                ->andWhere('pms.metroStationId = :metroStationId')
                ->setParameter('metroStationId', $filters['metroStationId']);
        }

        if (isset($filters['minGuests'])) {
            $qb->andWhere('p.maxDailyGuests IS NOT NULL')
                ->andWhere('p.maxDailyGuests >= :minGuests')
                ->setParameter('minGuests', $filters['minGuests']);
        }
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     */
    private function applyPropertyTypeAndRoomsFilters($qb, array $filters): void
    {
        $roomsList = (isset($filters['rooms']) && \is_array($filters['rooms']) && $filters['rooms'] !== [])
            ? $this->normalizeRoomsFilterList($filters['rooms'])
            : null;
        $roomsRequested = $roomsList !== null && $roomsList !== [];

        $allowed = array_fill_keys(PropertyType::values(), true);
        $selectedTypes = null;
        if (isset($filters['types']) && \is_array($filters['types']) && $filters['types'] !== []) {
            $types = [];
            foreach ($filters['types'] as $t) {
                if (\is_string($t) && isset($allowed[$t])) {
                    $types[] = $t;
                }
            }
            $types = array_values(array_unique($types));
            $selectedTypes = $types !== [] ? $types : null;
        } elseif (isset($filters['type'])) {
            $selectedTypes = [$filters['type']];
        }

        if (!$roomsRequested) {
            if ($selectedTypes !== null) {
                if (\count($selectedTypes) === 1) {
                    $qb->andWhere('p.type = :type')
                        ->setParameter('type', $selectedTypes[0]);
                } else {
                    $qb->andWhere('p.type IN (:types)')
                        ->setParameter('types', $selectedTypes, ArrayParameterType::STRING);
                }
            }

            return;
        }

        $roomCountable = self::ROOM_COUNT_PROPERTY_TYPES;
        $roomsDql = $this->buildPublishedRoomsPredicateDql($qb, $roomsList);

        if ($selectedTypes === null) {
            $qb->andWhere('p.type IN (:roomCountTypes)')
                ->setParameter('roomCountTypes', $roomCountable, ArrayParameterType::STRING)
                ->andWhere($roomsDql);

            return;
        }

        $withRoomCount = array_values(array_intersect($selectedTypes, $roomCountable));
        $withoutRoomCount = array_values(array_diff($selectedTypes, $roomCountable));

        if ($withRoomCount === []) {
            if (\count($selectedTypes) === 1) {
                $qb->andWhere('p.type = :type')
                    ->setParameter('type', $selectedTypes[0]);
            } else {
                $qb->andWhere('p.type IN (:types)')
                    ->setParameter('types', $selectedTypes, ArrayParameterType::STRING);
            }

            return;
        }

        if ($withoutRoomCount === []) {
            if (\count($withRoomCount) === 1) {
                $qb->andWhere('p.type = :type')
                    ->setParameter('type', $withRoomCount[0])
                    ->andWhere($roomsDql);
            } else {
                $qb->andWhere('p.type IN (:types)')
                    ->setParameter('types', $withRoomCount, ArrayParameterType::STRING)
                    ->andWhere($roomsDql);
            }

            return;
        }

        $qb->andWhere(
            '(p.type IN (:typesWithoutRoomCount)) OR (p.type IN (:typesWithRoomCount) AND ' . $roomsDql . ')'
        )
            ->setParameter('typesWithoutRoomCount', $withoutRoomCount, ArrayParameterType::STRING)
            ->setParameter('typesWithRoomCount', $withRoomCount, ArrayParameterType::STRING);
    }

    /**
     * @param list<mixed> $raw
     *
     * @return list<int>|null
     */
    private function normalizeRoomsFilterList(array $raw): ?array
    {
        $out = [];
        foreach ($raw as $v) {
            $n = (int) $v;
            if ($n >= 1 && $n <= 4) {
                $out[] = $n;
            }
        }
        $out = array_values(array_unique($out));
        sort($out);

        return $out === [] ? null : $out;
    }

    /**
     * @param list<int> $roomsList
     */
    private function buildPublishedRoomsPredicateDql(QueryBuilder $qb, array $roomsList): string
    {
        $exact = [];
        $gte4 = false;
        foreach ($roomsList as $v) {
            $v = (int) $v;
            if ($v === 4) {
                $gte4 = true;
            } elseif ($v >= 1 && $v <= 3) {
                $exact[] = $v;
            }
        }
        $exact = array_values(array_unique($exact));
        sort($exact);

        $chunks = [];
        if ($exact !== []) {
            if (\count($exact) === 1) {
                $qb->setParameter('__prSingle', $exact[0]);
                $chunks[] = 'p.rooms = :__prSingle';
            } else {
                $qb->setParameter('__prMany', $exact, ArrayParameterType::INTEGER);
                $chunks[] = 'p.rooms IN (:__prMany)';
            }
        }
        if ($gte4) {
            $qb->setParameter('__prGte4', 4);
            $chunks[] = 'p.rooms >= :__prGte4';
        }

        if ($chunks === []) {
            return '1 = 0';
        }
        if (\count($chunks) === 1) {
            return $chunks[0];
        }

        return '(' . implode(' OR ', $chunks) . ')';
    }

    public function findByOwner(string $ownerId, int $page = 1, int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPublishedByOwner(
        string $ownerId,
        int $limit = 10,
        ?int $excludePropertyId = null,
        ?string $type = null,
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.ownerId = :ownerId')
            ->setParameter('status', 'published')
            ->setParameter('ownerId', $ownerId);

        if ($excludePropertyId !== null) {
            $qb->andWhere('p.id != :excludePropertyId')
                ->setParameter('excludePropertyId', $excludePropertyId);
        }

        if ($type !== null) {
            $qb->andWhere('p.type = :type')
                ->setParameter('type', $type);
        }

        $todayStart = new \DateTimeImmutable('today');
        $qb->addSelect('(CASE WHEN p.boostedAt >= :boostTodayStart THEN 1 ELSE 0 END) AS HIDDEN boostScore')
            ->setParameter('boostTodayStart', $todayStart)
            ->orderBy('boostScore', 'DESC')
            ->addOrderBy('p.boostedAt', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countByOwner(string $ownerId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function delete(Property $property): void
    {
        $this->getEntityManager()->remove($property);
        $this->getEntityManager()->flush();
    }
}