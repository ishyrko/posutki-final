<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\Property;
use App\Domain\Shared\ValueObject\Id;

interface PropertyRepositoryInterface
{
    public function save(Property $property): void;

    public function findById(Id $id): ?Property;

    public function findByCalendarExportToken(string $token): ?Property;

    /**
     * @return Property[]
     */
    public function findWithExternalCalendarUrls(): array;

    public function findPublished(array $filters = [], int $page = 1, int $limit = 20): array;

    /**
     * @return Property[]
     */
    public function findPublishedOlderThan(\DateTimeImmutable $date): array;

    public function delete(Property $property): void;

    public function count(array $filters = []): int;

    public function findByOwner(string $ownerId, int $page = 1, int $limit = 20): array;

    public function countByOwner(string $ownerId): int;

    /**
     * @return Property[]
     */
    public function findPublishedByOwner(
        string $ownerId,
        int $limit = 10,
        ?int $excludePropertyId = null,
        ?string $type = null,
    ): array;

    /**
     * @return Property[]
     */
    public function findPublishedForReshuffle(): array;

    /**
     * @return Property[]
     */
    public function findWithExpiredPlacement(?\DateTimeImmutable $now = null): array;

    /**
     * Published listings whose VIP level expires within the given window and have not been reminded yet.
     *
     * @return Property[]
     */
    public function findWithPlacementLevelExpiringSoon(
        \DateTimeImmutable $now,
        \DateTimeImmutable $until,
    ): array;

    /**
     * @return int[] city ids that have at least one listing of the given property type
     */
    public function findCityIdsWithListings(string $propertyType): array;

    /**
     * Published listings grouped by placement_effective_level for a tariff scope.
     * Apartments: filter by cityId. Houses: filter by regionId (via city → district → region).
     *
     * @return array<int, int> effectiveLevel => count
     */
    public function countPublishedByEffectiveLevel(
        string $propertyType,
        ?int $cityId = null,
        ?int $regionId = null,
    ): array;

    /**
     * How many listings currently hold the given base VIP level in a tariff scope.
     * Counts published + moderation with a non-expired placement. Used for capacity.
     */
    public function countOccupiedAtBaseLevel(
        string $propertyType,
        int $level,
        ?int $cityId = null,
        ?int $regionId = null,
        ?\DateTimeImmutable $now = null,
        ?int $excludePropertyId = null,
    ): int;
}