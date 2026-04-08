<?php

declare(strict_types=1);

namespace App\Domain\Favorite\Repository;

use App\Domain\Favorite\Entity\Favorite;
use App\Domain\Shared\ValueObject\Id;

interface FavoriteRepositoryInterface
{
    public function save(Favorite $favorite): void;

    public function delete(Favorite $favorite): void;

    public function findByUserAndProperty(Id $userId, Id $propertyId): ?Favorite;

    /** @return int[] Property IDs */
    public function findPropertyIdsByUser(Id $userId): array;

    /** @return Favorite[] */
    public function findByUser(Id $userId, int $page = 1, int $limit = 20): array;

    public function countByUser(Id $userId): int;

    public function countByProperty(Id $propertyId): int;

    /**
     * @return array<int, array{date:string, count:int}>
     */
    public function findDailyCountsByProperty(Id $propertyId, int $days): array;
}
