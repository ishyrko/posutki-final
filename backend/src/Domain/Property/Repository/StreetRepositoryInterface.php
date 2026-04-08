<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\Street;

interface StreetRepositoryInterface
{
    public function findById(int $id): ?Street;

    /** @return Street[] */
    public function findByCityId(int $cityId): array;

    /** @return Street[] */
    public function searchByCityId(int $cityId, string $query): array;
}
