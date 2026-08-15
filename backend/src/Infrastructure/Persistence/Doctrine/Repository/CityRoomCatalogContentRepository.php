<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\CityRoomCatalogContent;
use App\Domain\Property\Repository\CityRoomCatalogContentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CityRoomCatalogContentRepository extends ServiceEntityRepository implements CityRoomCatalogContentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CityRoomCatalogContent::class);
    }

    public function findById(int $id): ?CityRoomCatalogContent
    {
        return $this->find($id);
    }

    public function findByCityIdAndRoomsBucket(int $cityId, int $roomsBucket): ?CityRoomCatalogContent
    {
        return $this->createQueryBuilder('r')
            ->where('r.cityId = :cityId')
            ->andWhere('r.roomsBucket = :roomsBucket')
            ->setParameter('cityId', $cityId)
            ->setParameter('roomsBucket', $roomsBucket)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<CityRoomCatalogContent>
     */
    public function findAllByCityId(int $cityId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.cityId = :cityId')
            ->setParameter('cityId', $cityId)
            ->orderBy('r.roomsBucket', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(CityRoomCatalogContent $content): void
    {
        $this->getEntityManager()->persist($content);
        $this->getEntityManager()->flush();
    }
}
