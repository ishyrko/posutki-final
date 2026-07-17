<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Property\ValueObject;

use App\Domain\Property\ValueObject\Price;
use PHPUnit\Framework\TestCase;

final class PriceTest extends TestCase
{
    public function testFromAmountStoresAmountAndCurrency(): void
    {
        $price = Price::fromAmount(125050, 'USD');

        self::assertSame(125050, $price->getAmount());
        self::assertSame('USD', $price->getCurrency());
    }

    public function testGetFormattedUsesWholeCurrencyUnits(): void
    {
        $price = Price::fromAmount(123456, 'BYN');

        self::assertSame('123 456 BYN', $price->getFormatted());
    }
}
