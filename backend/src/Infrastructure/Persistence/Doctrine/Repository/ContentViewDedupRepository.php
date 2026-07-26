<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Analytics\Repository\ContentViewDedupRepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final readonly class ContentViewDedupRepository implements ContentViewDedupRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function tryAcquireUniqueView(
        string $entityType,
        string $entityId,
        string $visitorKey,
        \DateTimeImmutable $viewDate,
    ): bool {
        $affected = $this->connection->executeStatement(
            'INSERT IGNORE INTO content_view_dedup (entity_type, entity_id, visitor_key, view_date, created_at)
             VALUES (:entityType, :entityId, :visitorKey, :viewDate, :createdAt)',
            [
                'entityType' => $entityType,
                'entityId' => $entityId,
                'visitorKey' => $visitorKey,
                'viewDate' => $viewDate->format('Y-m-d'),
                'createdAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ],
            [
                'entityType' => ParameterType::STRING,
                'entityId' => ParameterType::STRING,
                'visitorKey' => ParameterType::STRING,
                'viewDate' => ParameterType::STRING,
                'createdAt' => ParameterType::STRING,
            ],
        );

        return $affected > 0;
    }
}
