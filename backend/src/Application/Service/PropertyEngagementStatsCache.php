<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Property\Repository\PropertyEngagementStatsRepositoryInterface;

final class PropertyEngagementStatsCache
{
    /** @var array<int, array{distinctInquirers: int, distinctMessageSenders: int}> */
    private array $statsByPropertyId = [];

    public function __construct(
        private readonly PropertyEngagementStatsRepositoryInterface $propertyEngagementStatsRepository,
    ) {
    }

    /**
     * @param list<int> $propertyIds
     */
    public function warmUp(array $propertyIds): void
    {
        $propertyIds = array_values(array_unique(array_map('intval', $propertyIds)));
        if ($propertyIds === []) {
            return;
        }

        $missingPropertyIds = array_values(array_filter(
            $propertyIds,
            fn (int $propertyId): bool => !isset($this->statsByPropertyId[$propertyId]),
        ));

        if ($missingPropertyIds === []) {
            return;
        }

        foreach ($this->propertyEngagementStatsRepository->findCountsByPropertyIds($missingPropertyIds) as $propertyId => $stats) {
            $this->statsByPropertyId[(int) $propertyId] = $stats;
        }

        foreach ($missingPropertyIds as $propertyId) {
            $this->statsByPropertyId[$propertyId] ??= [
                'distinctInquirers' => 0,
                'distinctMessageSenders' => 0,
            ];
        }
    }

    public function getDistinctInquirers(int $propertyId): int
    {
        return $this->statsByPropertyId[$propertyId]['distinctInquirers'] ?? 0;
    }

    public function getDistinctMessageSenders(int $propertyId): int
    {
        return $this->statsByPropertyId[$propertyId]['distinctMessageSenders'] ?? 0;
    }
}
