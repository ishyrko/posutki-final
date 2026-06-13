<?php

declare(strict_types=1);

namespace App\Domain\Property\Validation;

use App\Domain\Property\Enum\DealType;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Shared\Exception\DomainException;

final class PropertyDailyPriceValidator
{
    public const MIN_DAILY_PRICE_BYN = 10;

    public static function assertValid(string $dealType, string $propertyType, int $priceByn): void
    {
        if ($dealType !== DealType::Daily->value) {
            return;
        }

        if (!in_array($propertyType, [PropertyType::Apartment->value, PropertyType::House->value], true)) {
            return;
        }

        if ($priceByn < self::MIN_DAILY_PRICE_BYN) {
            throw new DomainException(sprintf(
                'Минимальная цена за сутки — %d BYN',
                self::MIN_DAILY_PRICE_BYN,
            ));
        }
    }
}
