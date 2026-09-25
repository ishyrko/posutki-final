<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\RegionCatalogContent;

interface RegionCatalogContentRepositoryInterface
{
    public function findById(int $id): ?RegionCatalogContent;

    public function findByRegionId(int $regionId): ?RegionCatalogContent;

    public function save(RegionCatalogContent $content): void;
}
