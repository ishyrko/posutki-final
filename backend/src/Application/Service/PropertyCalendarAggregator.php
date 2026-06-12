<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyAvailabilityBlock;
use App\Domain\Property\Repository\PropertyAvailabilityBlockRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;

final class PropertyCalendarAggregator
{
    /** Снимок старше этого порога считается устаревшим (cron обновляет раз в час). */
    private const int STALE_SNAPSHOT_TTL_SECONDS = 7200;

    public function __construct(
        private readonly PropertyAvailabilityBlockRepositoryInterface $availabilityBlockRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
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
            'lastUpdatedAt' => $this->resolveCalendarLastUpdatedAt(
                $property->getId(),
                $importedData['lastUpdatedAt'],
            )?->format('c'),
        ];
    }

    public function getCalendarLastUpdatedAt(Property $property): ?\DateTimeImmutable
    {
        $importedData = $this->resolveImportedCalendarData($property);

        return $this->resolveCalendarLastUpdatedAt($property->getId(), $importedData['lastUpdatedAt']);
    }

    /**
     * @return array{
     *     blockedRanges: list<array{start: string, end: string}>,
     *     lastUpdatedAt: ?string
     * }
     */
    public function resolveImportedCalendarData(Property $property): array
    {
        $urls = $property->getExternalCalendarUrls() ?? [];
        if ($urls === []) {
            return [
                'blockedRanges' => [],
                'lastUpdatedAt' => null,
            ];
        }

        $snapshot = $property->getExternalCalendarSnapshot();
        $syncedAt = $property->getExternalCalendarSyncedAt();

        if ($snapshot !== null && !$this->isSnapshotStale($syncedAt)) {
            return [
                'blockedRanges' => $snapshot['blockedRanges'] ?? [],
                'lastUpdatedAt' => $syncedAt?->format('c'),
            ];
        }

        return $this->refreshImportedCalendarData($property, $urls);
    }

    private function isSnapshotStale(?\DateTimeImmutable $syncedAt): bool
    {
        if ($syncedAt === null) {
            return true;
        }

        return $syncedAt->getTimestamp() < (new \DateTimeImmutable())->getTimestamp() - self::STALE_SNAPSHOT_TTL_SECONDS;
    }

    /**
     * @param list<string> $urls
     *
     * @return array{
     *     blockedRanges: list<array{start: string, end: string}>,
     *     lastUpdatedAt: ?string
     * }
     */
    private function refreshImportedCalendarData(Property $property, array $urls): array
    {
        $calendarData = $this->icsCalendarService->fetchCalendarData($urls);

        if ($calendarData['successfulFetches'] === 0) {
            return [
                'blockedRanges' => [],
                'lastUpdatedAt' => null,
            ];
        }

        $syncedAt = $calendarData['lastUpdatedAt'] !== null
            ? new \DateTimeImmutable($calendarData['lastUpdatedAt'])
            : new \DateTimeImmutable();

        $property->setExternalCalendarSnapshot($calendarData['blockedRanges'], $syncedAt);
        $this->propertyRepository->save($property);

        return [
            'blockedRanges' => $calendarData['blockedRanges'],
            'lastUpdatedAt' => $property->getExternalCalendarSyncedAt()?->format('c'),
        ];
    }

    private function resolveCalendarLastUpdatedAt(Id $propertyId, ?string $importedLastUpdatedAt): ?\DateTimeImmutable
    {
        $importedAt = $importedLastUpdatedAt !== null
            ? new \DateTimeImmutable($importedLastUpdatedAt)
            : null;

        $manualAt = $this->availabilityBlockRepository->findLatestCreatedAtByPropertyId($propertyId);

        if ($importedAt === null) {
            return $manualAt;
        }

        if ($manualAt === null) {
            return $importedAt;
        }

        return $importedAt > $manualAt ? $importedAt : $manualAt;
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
