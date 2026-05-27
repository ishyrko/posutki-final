<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\BookingInquiry\Entity\BookingInquiry;
use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookingInquiryRepository extends ServiceEntityRepository implements BookingInquiryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookingInquiry::class);
    }

    public function save(BookingInquiry $inquiry): void
    {
        $this->getEntityManager()->persist($inquiry);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?BookingInquiry
    {
        return $this->find(Id::fromString($id)->getValue());
    }

    public function findByOwnerId(string $ownerId, int $page, int $limit): array
    {
        $offset = max(0, ($page - 1) * $limit);

        return $this->createQueryBuilder('b')
            ->andWhere('b.ownerId = :ownerId')
            ->setParameter('ownerId', Id::fromString($ownerId)->getValue())
            ->orderBy('b.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByOwnerId(string $ownerId): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.ownerId = :ownerId')
            ->setParameter('ownerId', Id::fromString($ownerId)->getValue())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUnreadByOwnerId(string $ownerId): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.ownerId = :ownerId')
            ->andWhere('b.isRead = :isRead')
            ->setParameter('ownerId', Id::fromString($ownerId)->getValue())
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAllAsReadByOwnerId(string $ownerId): void
    {
        $this->createQueryBuilder('b')
            ->update()
            ->set('b.isRead', ':isRead')
            ->andWhere('b.ownerId = :ownerId')
            ->andWhere('b.isRead = :wasUnread')
            ->setParameter('ownerId', Id::fromString($ownerId)->getValue())
            ->setParameter('isRead', true)
            ->setParameter('wasUnread', false)
            ->getQuery()
            ->execute();
    }
}
