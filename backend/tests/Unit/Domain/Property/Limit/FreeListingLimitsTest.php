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
}
