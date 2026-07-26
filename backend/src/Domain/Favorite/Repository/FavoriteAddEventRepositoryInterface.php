<?php

declare(strict_types=1);

namespace App\Domain\Favorite\Repository;

use App\Domain\Favorite\Entity\FavoriteAddEvent;
use App\Domain\Shared\ValueObject\Id;

interface FavoriteAddEventRepositoryInterface
{
    public function record(Id $propertyId): void;

    /**
     * @return array<int, array{date: string, count: int}>
     */
    public function findDailyCountsByProperty(Id $propertyId, int $days): array;
}
