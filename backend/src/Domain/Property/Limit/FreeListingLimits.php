<?php

declare(strict_types=1);

namespace App\Domain\Property\Limit;

final class FreeListingLimits
{
    public const MAX_PUBLISHED_PER_ACCOUNT = 5;

    public const DEFAULT_PARTNER_LISTING_LIMIT = 400;

    public const MAX_CATALOG_LISTINGS_PER_OWNER_PER_PAGE = 10;

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

    /**
     * Keep at most N items of one owner so a partner feed cannot dominate a catalog page.
     *
     * @template T
     *
     * @param list<T>              $items
     * @param callable(T): string  $ownerIdOf
     *
     * @return list<T>
     */
    public static function capItemsPerOwner(array $items, callable $ownerIdOf, ?int $maxPerOwner = null): array
    {
        $maxPerOwner ??= self::MAX_CATALOG_LISTINGS_PER_OWNER_PER_PAGE;
        $seen = [];
        $capped = [];
        foreach ($items as $item) {
            $ownerId = (string) $ownerIdOf($item);
            $seen[$ownerId] = ($seen[$ownerId] ?? 0) + 1;
            if ($seen[$ownerId] > $maxPerOwner) {
                continue;
            }
            $capped[] = $item;
        }

        return $capped;
    }
}
