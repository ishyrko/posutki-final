<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Enum\PlacementPurchaseStatus;
use App\Domain\Property\Enum\PlacementPurchaseType;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PropertyPlacementPurchase>
 */
class PropertyPlacementPurchaseRepository extends ServiceEntityRepository implements PropertyPlacementPurchaseRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyPlacementPurchase::class);
    }

    public function save(PropertyPlacementPurchase $purchase): void
    {
        $this->getEntityManager()->persist($purchase);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id): ?PropertyPlacementPurchase
    {
        return $this->find($id);
    }

    public function findByPropertyId(int $propertyId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.propertyId = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveSpecialByPropertyId(int $propertyId, ?\DateTimeImmutable $now = null): ?PropertyPlacementPurchase
    {
        $now ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('p')
            ->where('p.propertyId = :propertyId')
            ->andWhere('p.type = :type')
            ->andWhere('p.status = :status')
            ->andWhere('p.expiresAt > :now')
            ->setParameter('propertyId', $propertyId)
            ->setParameter('type', PlacementPurchaseType::Special->value)
            ->setParameter('status', PlacementPurchaseStatus::Active->value)
            ->setParameter('now', $now)
            ->orderBy('p.expiresAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveStandardByPropertyId(int $propertyId, ?\DateTimeImmutable $now = null): ?PropertyPlacementPurchase
    {
        $now ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('p')
            ->where('p.propertyId = :propertyId')
            ->andWhere('p.type = :type')
            ->andWhere('p.status = :status')
            ->andWhere('p.expiresAt > :now')
            ->setParameter('propertyId', $propertyId)
            ->setParameter('type', PlacementPurchaseType::Standard->value)
            ->setParameter('status', PlacementPurchaseStatus::Active->value)
            ->setParameter('now', $now)
            ->orderBy('p.expiresAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countOccupiedForSlot(int $slotId, ?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable();

        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.slotId = :slotId')
            ->andWhere(
                '(p.status = :active AND p.expiresAt > :now) OR (p.status = :pending AND p.reservationExpiresAt > :now)'
            )
            ->setParameter('slotId', $slotId)
            ->setParameter('active', PlacementPurchaseStatus::Active->value)
            ->setParameter('pending', PlacementPurchaseStatus::PendingPayment->value)
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findExpiredActive(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.expiresAt IS NOT NULL')
            ->andWhere('p.expiresAt <= :now')
            ->setParameter('status', PlacementPurchaseStatus::Active->value)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    public function findExpiredReservations(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.reservationExpiresAt IS NOT NULL')
            ->andWhere('p.reservationExpiresAt <= :now')
            ->setParameter('status', PlacementPurchaseStatus::PendingPayment->value)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    public function findPropertyIdsNeedingRecompute(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();
        $em = $this->getEntityManager();

        $fromPurchases = $em->createQueryBuilder()
            ->select('DISTINCT p.propertyId')
            ->from(PropertyPlacementPurchase::class, 'p')
            ->where('(p.status = :active AND p.expiresAt <= :now) OR (p.status = :pending AND p.reservationExpiresAt <= :now)')
            ->setParameter('active', PlacementPurchaseStatus::Active->value)
            ->setParameter('pending', PlacementPurchaseStatus::PendingPayment->value)
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $fromPurchases);
    }
}
