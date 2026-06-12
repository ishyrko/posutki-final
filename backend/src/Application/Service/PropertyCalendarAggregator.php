<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyAvailabilityBlock;
use App\Domain\Property\Repository\PropertyAvailabilityBlockRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;

final class PropertyCalendarAggregator
{
    public function __construct(
        private readonly PropertyAvailabilityBlockRepositoryInterface $availabilityBlockRepository,
        private readonly IcsCalendarService $icsCalendarService,
    ) {
    }

    /**
     * @return list<array{start: string, end: string}>
     */
    public function getManualBlockedRanges(Id $propertyId): array
    {
        return array_map(
            static fn(PropertyAvailabilityBlock $block): array => [
                'start' => $block->getStartDate()->format('Y-m-d'),
                'end' => $block->getEndDate()->format('Y-m-d'),
            ],
            $this->availabilityBlockRepository->findByPropertyId($propertyId),
        );
    }

    /**
     * @return array{
     *     blockedRanges: list<array{start: string, end: string}>,
     *     lastUpdatedAt: ?string
     * }
     */
    public function getPublicCalendarData(Property $property): array
    {
        $manualRanges = $this->getManualBlockedRanges($property->getId());
        $importedData = $this->resolveImportedCalendarData($property);

        return [
            'blockedRanges' => $this->mergeBlockedRanges([
                ...$manualRanges,
                ...$importedData['blockedRanges'],
            ]),
            'lastUpdatedAt' => $this->getCalendarLastUpdatedAt($property)?->format('c'),
        ];
    }

    public function getCalendarLastUpdatedAt(Property $property): ?\DateTimeImmutable
    {
        $importedData = $this->resolveImportedCalendarData($property);
        $importedAt = $importedData['lastUpdatedAt'] !== null
            ? new \DateTimeImmutable($importedData['lastUpdatedAt'])
            : null;

        $manualAt = $this->availabilityBlockRepository->findLatestCreatedAtByPropertyId($property->getId());

        if ($importedAt === null) {
            return $manualAt;
        }

        if ($manualAt === null) {
            return $importedAt;
        }

        return $importedAt > $manualAt ? $importedAt : $manualAt;
    }

    /**
     * @return array{
     *     blockedRanges: list<array{start: string, end: string}>,
     *     lastUpdatedAt: ?string
     * }
     */
    public function resolveImportedCalendarData(Property $property): array
    {
        $snapshot = $property->getExternalCalendarSnapshot();
        if ($snapshot !== null) {
            return [
                'blockedRanges' => $snapshot['blockedRanges'] ?? [],
                'lastUpdatedAt' => $property->getExternalCalendarSyncedAt()?->format('c'),
            ];
        }

        $urls = $property->getExternalCalendarUrls() ?? [];
        if ($urls === []) {
            return [
                'blockedRanges' => [],
                'lastUpdatedAt' => null,
            ];
        }

        return $this->icsCalendarService->fetchCalendarData($urls);
    }

    /**
     * @param list<array{start: string, end: string}> $ranges
     *
     * @return list<array{start: string, end: string}>
     */
    public function mergeBlockedRanges(array $ranges): array
    {
        if ($ranges === []) {
            return [];
        }

        usort($ranges, static fn(array $a, array $b): int => strcmp($a['start'], $b['start']));

        $merged = [];
        foreach ($ranges as $range) {
            if ($merged === []) {
                $merged[] = $range;
                continue;
            }

            $lastIndex = count($merged) - 1;
            $last = $merged[$lastIndex];
            $nextStart = (new \DateTimeImmutable($range['start']))->modify('-1 day')->format('Y-m-d');

            if ($range['start'] <= $last['end'] || $nextStart <= $last['end']) {
                $merged[$lastIndex]['end'] = max($last['end'], $range['end']);
                continue;
            }

            $merged[] = $range;
        }

        return $merged;
    }
}
