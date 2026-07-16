<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\Repository\PaymentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository implements PaymentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function save(Payment $payment): void
    {
        $this->getEntityManager()->persist($payment);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id): ?Payment
    {
        return $this->find($id);
    }

    public function findByCheckoutToken(string $checkoutToken): ?Payment
    {
        return $this->findOneBy(['checkoutToken' => $checkoutToken]);
    }

    public function findByTrackingId(string $trackingId): ?Payment
    {
        return $this->findOneBy(['trackingId' => $trackingId]);
    }

    public function findLatestForPurchase(int $purchaseId): ?Payment
    {
        return $this->createQueryBuilder('p')
            ->where('p.purchaseId = :purchaseId')
            ->setParameter('purchaseId', $purchaseId)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
