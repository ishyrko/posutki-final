<?php

declare(strict_types=1);

namespace App\Domain\Property\Validation;

use App\Domain\Shared\Exception\DomainException;

final class PropertyDailyPriceValidator
{
    public const MIN_DAILY_PRICE_BYN = 10;

    public static function assertValid(int $priceByn): void
    {
        if ($priceByn < self::MIN_DAILY_PRICE_BYN) {
            throw new DomainException(sprintf(
                'Минимальная цена за сутки — %d BYN',
                self::MIN_DAILY_PRICE_BYN,
            ));
        }
    }
}
