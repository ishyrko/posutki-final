<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

interface PropertyEngagementStatsRepositoryInterface
{
    /**
     * @param list<int> $propertyIds
     *
     * @return array<int, array{distinctInquirers: int, distinctMessageSenders: int}>
     */
    public function findCountsByPropertyIds(array $propertyIds): array;
}
