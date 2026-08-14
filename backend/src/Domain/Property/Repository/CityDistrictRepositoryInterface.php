<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\CityDistrict;

interface CityDistrictRepositoryInterface
{
    public function findById(int $id): ?CityDistrict;

    public function findByCityIdAndOfficialName(int $cityId, string $officialName): ?CityDistrict;

    /** @deprecated use findByCityIdAndOfficialName */
    public function findByCityIdAndName(int $cityId, string $name): ?CityDistrict;

    public function findByCityIdAndSlug(int $cityId, string $slug): ?CityDistrict;

    /**
     * @return list<CityDistrict>
     */
    public function findAllByCityId(int $cityId): array;

    /**
     * @return list<CityDistrict>
     */
    public function findAllWithoutSlug(): array;

    public function save(CityDistrict $cityDistrict): void;
}
