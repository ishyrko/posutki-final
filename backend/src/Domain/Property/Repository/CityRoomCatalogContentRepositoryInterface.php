<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\CityRoomCatalogContent;

interface CityRoomCatalogContentRepositoryInterface
{
    public function findById(int $id): ?CityRoomCatalogContent;

    public function findByCityIdAndRoomsBucket(int $cityId, int $roomsBucket): ?CityRoomCatalogContent;

    /**
     * @return list<CityRoomCatalogContent>
     */
    public function findAllByCityId(int $cityId): array;

    public function save(CityRoomCatalogContent $content): void;
}
