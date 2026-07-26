<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Repository;

interface ContentViewDedupRepositoryInterface
{
    /**
     * @return bool true when this visitor has not been counted today for the entity
     */
    public function tryAcquireUniqueView(
        string $entityType,
        string $entityId,
        string $visitorKey,
        \DateTimeImmutable $viewDate,
    ): bool;
}
