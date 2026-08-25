<?php

declare(strict_types=1);

namespace App\Domain\Review\Repository;

use App\Domain\Review\Entity\Review;
use App\Domain\Shared\ValueObject\Id;

interface ReviewRepositoryInterface
{
    public function save(Review $review): void;

    public function delete(Review $review): void;

    public function findById(Id $id): ?Review;

    /** Активный (не deleted) отзыв автора по объявлению. */
    public function findActiveByAuthorAndProperty(Id $authorId, Id $propertyId): ?Review;

    /**
     * Все активные (не deleted) отзывы автора.
     *
     * @return Review[]
     */
    public function findActiveByAuthorId(Id $authorId): array;

    /**
     * @return Review[]
     */
    public function findApprovedByPropertyId(Id $propertyId): array;

    /**
     * @return Review[]
     */
    public function findApprovedByOwnerId(Id $ownerId): array;

    /**
     * @return Review[]
     */
    public function findApprovedByPropertyIdForOwner(Id $propertyId, Id $ownerId): array;

    /**
     * @return array<int, int> propertyId => unviewed count
     */
    public function countUnviewedGroupedByPropertyForOwner(Id $ownerId): array;

    public function markSeenForPropertyOwner(Id $propertyId, Id $ownerId): void;

    /**
     * @return array{avg: float|null, count: int}
     */
    public function getAggregateByPropertyId(Id $propertyId): array;
}
