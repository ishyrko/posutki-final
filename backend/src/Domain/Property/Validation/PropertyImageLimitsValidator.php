<?php

declare(strict_types=1);

namespace App\Domain\Property\Validation;

use App\Domain\Property\Enum\PropertyType;
use App\Domain\Shared\Exception\DomainException;

final class PropertyImageLimitsValidator
{
    public const MIN = 3;
    public const MAX_APARTMENT = 20;
    public const MAX_HOUSE = 30;

    public static function maxForType(string $propertyType): int
    {
        return $propertyType === PropertyType::House->value
            ? self::MAX_HOUSE
            : self::MAX_APARTMENT;
    }

    public static function assertValid(string $propertyType, int $count): void
    {
        if ($count < self::MIN) {
            throw new DomainException(sprintf('Загрузите не менее %d фотографий', self::MIN));
        }

        $max = self::maxForType($propertyType);
        if ($count > $max) {
            throw new DomainException(sprintf('Не более %d фотографий', $max));
        }
    }
}
