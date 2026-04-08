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
    public function testDailyRoomIsInvalid(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Посуточная сдача комнат недоступна');

        PropertyDealCombinationValidator::assertValid(DealType::Daily->value, PropertyType::Room->value);
    }

    public function testDailyApartmentIsValid(): void
    {
        PropertyDealCombinationValidator::assertValid(DealType::Daily->value, PropertyType::Apartment->value);
        $this->addToAssertionCount(1);
    }

    public function testRentLandIsInvalid(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Аренда участков недоступна');

        PropertyDealCombinationValidator::assertValid(DealType::Rent->value, PropertyType::Land->value);
    }
}
