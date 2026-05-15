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

    public function findByAuthorAndProperty(Id $authorId, Id $propertyId): ?Review;

    /**
     * @return Review[]
     */
    public function findApprovedByPropertyId(Id $propertyId): array;

    /**
     * @return array{avg: float|null, count: int}
     */
    public function getAggregateByPropertyId(Id $propertyId): array;
}
