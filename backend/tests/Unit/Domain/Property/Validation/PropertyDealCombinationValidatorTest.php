<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Property\Validation;

use App\Domain\Property\Enum\DealType;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Validation\PropertyDealCombinationValidator;
use App\Domain\Shared\Exception\DomainException;
use PHPUnit\Framework\TestCase;

final class PropertyDealCombinationValidatorTest extends TestCase
{
    public function testDailyApartmentIsValid(): void
    {
        PropertyDealCombinationValidator::assertValid(DealType::Daily->value, PropertyType::Apartment->value);
        $this->addToAssertionCount(1);
    }

    public function testDailyHouseIsValid(): void
    {
        PropertyDealCombinationValidator::assertValid(DealType::Daily->value, PropertyType::House->value);
        $this->addToAssertionCount(1);
    }

    public function testNonDailyDealIsInvalid(): void
    {
        $this->expectException(DomainException::class);
        PropertyDealCombinationValidator::assertValid('sale', PropertyType::Apartment->value);
    }

    public function testUnsupportedPropertyTypeIsInvalid(): void
    {
        $this->expectException(DomainException::class);
        PropertyDealCombinationValidator::assertValid(DealType::Daily->value, 'garage');
    }
}
