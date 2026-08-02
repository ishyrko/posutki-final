<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\CityDistrict;

interface CityDistrictRepositoryInterface
{
    public function findById(int $id): ?CityDistrict;

    public function findByCityIdAndName(int $cityId, string $name): ?CityDistrict;

    public function save(CityDistrict $cityDistrict): void;
}
