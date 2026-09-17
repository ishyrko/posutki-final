<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Property\Limit;

use App\Domain\Property\Limit\FreeListingLimits;
use PHPUnit\Framework\TestCase;

final class FreeListingLimitsTest extends TestCase
{
    public function testCalculateCityApartmentLimit(): void
    {
        self::assertSame(10, FreeListingLimits::calculateCityApartmentLimit(0));
        self::assertSame(1, FreeListingLimits::calculateCityApartmentLimit(9));
        self::assertSame(1, FreeListingLimits::calculateCityApartmentLimit(15));
    }

    public function testCapItemsPerOwnerKeepsAtMostTenPerOwner(): void
    {
        $items = [];
        for ($id = 1; $id <= 11; ++$id) {
            $items[] = ['id' => $id, 'owner' => 'a'];
        }
        $items[] = ['id' => 12, 'owner' => 'b'];
        $items[] = ['id' => 13, 'owner' => 'b'];

        $capped = FreeListingLimits::capItemsPerOwner(
            $items,
            static fn(array $item): string => $item['owner'],
        );

        self::assertSame([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 13], array_column($capped, 'id'));
        self::assertCount(
            FreeListingLimits::MAX_CATALOG_LISTINGS_PER_OWNER_PER_PAGE,
            array_filter($capped, static fn(array $item): bool => $item['owner'] === 'a'),
        );
    }
}
