<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Property\Validation;

use App\Domain\Property\Enum\DealType;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Validation\PropertyDailyPriceValidator;
use App\Domain\Shared\Exception\DomainException;
use PHPUnit\Framework\TestCase;

final class PropertyDailyPriceValidatorTest extends TestCase
{
    public function testMinimumDailyPriceForApartment(): void
    {
        PropertyDailyPriceValidator::assertValid(
            DealType::Daily->value,
            PropertyType::Apartment->value,
            PropertyDailyPriceValidator::MIN_DAILY_PRICE_BYN,
        );
        $this->addToAssertionCount(1);
    }

    public function testPriceBelowMinimumIsInvalid(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Минимальная цена за сутки — 10 BYN');

        PropertyDailyPriceValidator::assertValid(
            DealType::Daily->value,
            PropertyType::House->value,
            PropertyDailyPriceValidator::MIN_DAILY_PRICE_BYN - 1,
        );
    }
}
