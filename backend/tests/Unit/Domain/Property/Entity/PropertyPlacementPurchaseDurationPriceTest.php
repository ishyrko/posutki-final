<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Property\Entity;

use App\Domain\Property\Entity\PropertyPlacementPurchase;
use PHPUnit\Framework\TestCase;

final class PropertyPlacementPurchaseDurationPriceTest extends TestCase
{
    public function testPriceForDurationAppliesConfiguredDiscounts(): void
    {
        self::assertSame(49, PropertyPlacementPurchase::priceForDuration(49, 1));
        self::assertSame(140, PropertyPlacementPurchase::priceForDuration(49, 3));
        self::assertSame(265, PropertyPlacementPurchase::priceForDuration(49, 6));
        self::assertSame(470, PropertyPlacementPurchase::priceForDuration(49, 12));
    }

    public function testDiscountPercentForDuration(): void
    {
        self::assertSame(0, PropertyPlacementPurchase::discountPercentForDuration(1));
        self::assertSame(5, PropertyPlacementPurchase::discountPercentForDuration(3));
        self::assertSame(10, PropertyPlacementPurchase::discountPercentForDuration(6));
        self::assertSame(20, PropertyPlacementPurchase::discountPercentForDuration(12));
    }
}
