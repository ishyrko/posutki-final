<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\NationwideHouseCatalogContent;

interface NationwideHouseCatalogContentRepositoryInterface
{
    public function findSingle(): ?NationwideHouseCatalogContent;

    public function save(NationwideHouseCatalogContent $content): void;
}
