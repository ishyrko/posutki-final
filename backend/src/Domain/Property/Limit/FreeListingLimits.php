<?php

declare(strict_types=1);

namespace App\Domain\Property\Limit;

final class FreeListingLimits
{
    public const MAX_PUBLISHED_PER_ACCOUNT = 5;

    public const CITY_APARTMENT_BASE = 10;

    public const CITY_APARTMENT_MIN = 1;

    public static function calculateCityApartmentLimit(int $publishedApartmentsInCity): int
    {
        return max(self::CITY_APARTMENT_MIN, self::CITY_APARTMENT_BASE - $publishedApartmentsInCity);
    }

    /** Лимит бесплатных объявлений на одного пользователя с учётом потолка аккаунта. */
    public static function perUserDisplayLimit(int $cityApartmentLimit): int
    {
        return min(self::MAX_PUBLISHED_PER_ACCOUNT, max(self::CITY_APARTMENT_MIN, $cityApartmentLimit));
    }
}
