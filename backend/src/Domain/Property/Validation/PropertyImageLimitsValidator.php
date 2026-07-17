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
    /** Max photos shown publicly for free placement. */
    public const MAX_VISIBLE_FREE_PLACEMENT = 5;

    public static function maxForType(string $propertyType): int
    {
        return $propertyType === PropertyType::House->value
            ? self::MAX_HOUSE
            : self::MAX_APARTMENT;
    }

    /**
     * @param list<string> $images
     * @return list<string>
     */
    public static function visibleForPlacement(array $images, int $placementEffectiveLevel): array
    {
        $images = array_values($images);
        if (self::allowsExtraMedia($placementEffectiveLevel)) {
            return $images;
        }

        return array_slice($images, 0, self::MAX_VISIBLE_FREE_PLACEMENT);
    }

    /** Video, Instagram and website are public only with VIP (effective level &gt; 0). */
    public static function allowsExtraMedia(int $placementEffectiveLevel): bool
    {
        return $placementEffectiveLevel > 0;
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
