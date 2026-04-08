<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\City;

interface CityRepositoryInterface
{
    public function findById(int $id): ?City;

    public function findBySlug(string $slug): ?City;

    /** @return City[] */
    public function findByDistrictId(int $districtId): array;

    /** @return City[] */
    public function searchByName(string $query, ?int $districtId = null, int $limit = 20): array;
}
