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
     * @return int[] city ids that have at least one listing of the given property type
     */
    public function findCityIdsWithListings(string $propertyType): array;
}