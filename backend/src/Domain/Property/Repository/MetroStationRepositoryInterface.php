<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\MetroStation;

interface MetroStationRepositoryInterface
{
    /** @return MetroStation[] */
    public function findAll(): array;

    /** @return MetroStation[] */
    public function findByCityId(int $cityId): array;

    public function findById(int $id): ?MetroStation;

    /** @return MetroStation[] */
    public function findByIds(array $ids): array;
}
