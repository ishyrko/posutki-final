<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\CityMicrodistrict;

interface CityMicrodistrictRepositoryInterface
{
    public function findById(int $id): ?CityMicrodistrict;

    public function findByCityIdAndOfficialName(int $cityId, string $officialName): ?CityMicrodistrict;

    public function findByCityIdAndSlug(int $cityId, string $slug): ?CityMicrodistrict;

    /**
     * @return list<CityMicrodistrict>
     */
    public function findAllByCityId(int $cityId): array;

    public function save(CityMicrodistrict $microdistrict): void;
}
