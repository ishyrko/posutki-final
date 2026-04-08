<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\Region;

interface RegionRepositoryInterface
{
    public function findById(int $id): ?Region;

    /** @return Region[] */
    public function findAll(): array;
}
