<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Property\Repository\PropertyEngagementStatsRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class PropertyEngagementStatsRepository implements PropertyEngagementStatsRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findCountsByPropertyIds(array $propertyIds): array
    {
        $propertyIds = array_values(array_unique(array_map('intval', $propertyIds)));
        if ($propertyIds === []) {
            return [];
        }

        $stats = [];
        foreach ($propertyIds as $propertyId) {
            $stats[$propertyId] = [
                'distinctInquirers' => 0,
                'distinctMessageSenders' => 0,
            ];
        }

        $connection = $this->entityManager->getConnection();
        $placeholders = implode(', ', array_fill(0, count($propertyIds), '?'));

        $inquiryRows = $connection->executeQuery(
            sprintf(
                'SELECT property_id, COUNT(DISTINCT person_key) AS person_count
                 FROM (
                     SELECT property_id,
                            CASE
                                WHEN user_id IS NOT NULL THEN CONCAT(\'u:\', user_id)
                                ELSE CONCAT(\'p:\', phone)
                            END AS person_key
                     FROM booking_inquiries
                     WHERE property_id IN (%s)
                 ) inquiries
                 GROUP BY property_id',
                $placeholders,
            ),
            $propertyIds,
        )->fetchAllAssociative();

        foreach ($inquiryRows as $row) {
            $propertyId = (int) $row['property_id'];
            if (!isset($stats[$propertyId])) {
                continue;
            }

            $stats[$propertyId]['distinctInquirers'] = (int) $row['person_count'];
        }

        $messageRows = $connection->executeQuery(
            sprintf(
                'SELECT c.property_id, COUNT(DISTINCT m.sender_id) AS sender_count
                 FROM messages m
                 INNER JOIN conversations c ON c.id = m.conversation_id
                 WHERE c.property_id IN (%s)
                   AND m.sender_id != c.seller_id
                 GROUP BY c.property_id',
                $placeholders,
            ),
            $propertyIds,
        )->fetchAllAssociative();

        foreach ($messageRows as $row) {
            $propertyId = (int) $row['property_id'];
            if (!isset($stats[$propertyId])) {
                continue;
            }

            $stats[$propertyId]['distinctMessageSenders'] = (int) $row['sender_count'];
        }

        return $stats;
    }
}
