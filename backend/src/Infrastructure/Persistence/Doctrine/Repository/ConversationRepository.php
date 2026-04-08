<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Message\Entity\Conversation;
use App\Domain\Message\Repository\ConversationRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConversationRepository extends ServiceEntityRepository implements ConversationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    public function save(Conversation $conversation): void
    {
        $this->getEntityManager()->persist($conversation);
        $this->getEntityManager()->flush();
    }

    public function findById(Id $id): ?Conversation
    {
        return $this->find($id->getValue());
    }

    public function findByPropertyAndBuyer(string $propertyId, string $buyerId): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->where('c.propertyId = :propertyId')
            ->andWhere('c.buyerId = :buyerId')
            ->setParameter('propertyId', $propertyId)
            ->setParameter('buyerId', $buyerId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUser(string $userId, int $page = 1, int $limit = 20): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.sellerId = :userId OR c.buyerId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('c.lastMessageAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByUser(string $userId): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.sellerId = :userId OR c.buyerId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUnreadByUser(string $userId): int
    {
        $asSeller = (int) $this->createQueryBuilder('c')
            ->select('SUM(c.unreadSeller)')
            ->where('c.sellerId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        $asBuyer = (int) $this->createQueryBuilder('c')
            ->select('SUM(c.unreadBuyer)')
            ->where('c.buyerId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return $asSeller + $asBuyer;
    }
}
