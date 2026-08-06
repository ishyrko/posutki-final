<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\Landmark;

interface LandmarkRepositoryInterface
{
    /** @return Landmark[] */
    public function findActiveByCityId(int $cityId): array;

    public function findByCityIdAndSlug(int $cityId, string $slug): ?Landmark;

    public function findAnyByCityIdAndSlug(int $cityId, string $slug): ?Landmark;

    public function findById(int $id): ?Landmark;

    /** @param int[] $ids @return Landmark[] */
    public function findActiveByIds(array $ids): array;

    public function save(Landmark $landmark): void;

    public function delete(Landmark $landmark): void;
}
