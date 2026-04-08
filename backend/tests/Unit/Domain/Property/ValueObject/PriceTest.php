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

    public function testGetFormattedUsesTwoDecimalPlaces(): void
    {
        $price = Price::fromAmount(123456, 'BYN');

        self::assertSame('1 234.56 BYN', $price->getFormatted());
    }

    public function testCalculatePricePerMeterReturnsNewPrice(): void
    {
        $price = Price::fromAmount(300000, 'BYN');

        $perMeter = $price->calculatePricePerMeter(50.0);

        self::assertSame(6000, $perMeter->getAmount());
        self::assertSame('BYN', $perMeter->getCurrency());
    }

    public function testCalculatePricePerMeterThrowsWhenAreaIsZero(): void
    {
        $price = Price::fromAmount(300000, 'BYN');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Площадь должна быть больше нуля');

        $price->calculatePricePerMeter(0.0);
    }
}
