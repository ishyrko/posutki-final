<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\ResidentialComplex;

interface ResidentialComplexRepositoryInterface
{
    public function findById(int $id): ?ResidentialComplex;

    public function findByCityIdAndOfficialName(int $cityId, string $officialName): ?ResidentialComplex;

    public function findByCityIdAndSlug(int $cityId, string $slug): ?ResidentialComplex;

    /**
     * @return list<ResidentialComplex>
     */
    public function findAllByCityId(int $cityId): array;

    public function save(ResidentialComplex $complex): void;
}
