<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Property\Validation;

use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Validation\PropertyImageLimitsValidator;
use App\Domain\Shared\Exception\DomainException;
use PHPUnit\Framework\TestCase;

final class PropertyImageLimitsValidatorTest extends TestCase
{
    public function testMaxForApartment(): void
    {
        self::assertSame(20, PropertyImageLimitsValidator::maxForType(PropertyType::Apartment->value));
    }

    public function testMaxForHouse(): void
    {
        self::assertSame(30, PropertyImageLimitsValidator::maxForType(PropertyType::House->value));
    }

    public function testAssertValidRejectsTooManyApartmentPhotos(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Не более 20 фотографий');

        PropertyImageLimitsValidator::assertValid(PropertyType::Apartment->value, 21);
    }

    public function testAssertValidAcceptsHouseLimit(): void
    {
        PropertyImageLimitsValidator::assertValid(PropertyType::House->value, 30);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Не более 30 фотографий');

        PropertyImageLimitsValidator::assertValid(PropertyType::House->value, 31);
    }

    public function testVisibleForFreePlacementCapsAtFive(): void
    {
        $images = ['a', 'b', 'c', 'd', 'e', 'f', 'g'];

        self::assertSame(
            ['a', 'b', 'c', 'd', 'e'],
            PropertyImageLimitsValidator::visibleForPlacement($images, 0),
        );
        self::assertSame(
            $images,
            PropertyImageLimitsValidator::visibleForPlacement($images, 1),
        );
    }

    public function testAllowsExtraMediaOnlyWithVip(): void
    {
        self::assertFalse(PropertyImageLimitsValidator::allowsExtraMedia(0));
        self::assertTrue(PropertyImageLimitsValidator::allowsExtraMedia(1));
    }
}
