<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Message\Entity\Message;
use App\Domain\Message\Repository\MessageRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository implements MessageRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function save(Message $message): void
    {
        $this->getEntityManager()->persist($message);
        $this->getEntityManager()->flush();
    }

    public function findByConversation(string $conversationId, int $page = 1, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.conversationId = :conversationId')
            ->setParameter('conversationId', $conversationId)
            ->orderBy('m.createdAt', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByConversation(string $conversationId): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.conversationId = :conversationId')
            ->setParameter('conversationId', $conversationId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAllReadInConversation(string $conversationId, string $readByUserId): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', ':true')
            ->where('m.conversationId = :conversationId')
            ->andWhere('m.senderId != :userId')
            ->andWhere('m.isRead = :false')
            ->setParameter('true', true)
            ->setParameter('false', false)
            ->setParameter('conversationId', $conversationId)
            ->setParameter('userId', $readByUserId)
            ->getQuery()
            ->execute();
    }

    public function findDailyReceivedCountsByProperty(int $propertyId, int $days): array
    {
        $endDate = (new \DateTimeImmutable('today'))->setTime(23, 59, 59);
        $startDate = $endDate->modify(sprintf('-%d days', max(0, $days - 1)))->setTime(0, 0);

        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT DATE(m.created_at) AS stat_date, COUNT(m.id) AS stat_count
             FROM messages m
             INNER JOIN conversations c ON c.id = m.conversation_id
             WHERE c.property_id = :propertyId
               AND m.sender_id != c.seller_id
               AND m.created_at >= :startDate
               AND m.created_at <= :endDate
             GROUP BY DATE(m.created_at)
             ORDER BY DATE(m.created_at) ASC',
            [
                'propertyId' => $propertyId,
                'startDate' => $startDate->format('Y-m-d H:i:s'),
                'endDate' => $endDate->format('Y-m-d H:i:s'),
            ],
        )->fetchAllAssociative();

        return array_map(
            static fn(array $row): array => [
                'date' => (string) $row['stat_date'],
                'count' => (int) $row['stat_count'],
            ],
            $rows,
        );
    }
}
