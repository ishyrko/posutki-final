<?php

declare(strict_types=1);

namespace App\Domain\Property\Service;

/**
 * Slugs городов с посуточным каталогом квартир — в синхроне с frontend
 * CATALOG_APARTMENT_LOCATION / HOME_CITY_FILTERS.
 */
final class CatalogApartmentCitySlugs
{
    /** @var list<string> */
    public const ORDERED = [
        'minsk',
        'brest',
        'vitebsk',
        'grodno',
        'gomel',
        'mogilev',
        'baranovichi',
        'pinsk',
        'bobruysk',
        'molodechno',
        'logoysk',
        'orsha',
        'novopolotsk',
        'svetlogorsk',
        'smorgon',
        'zhlobin',
        'volkovysk',
        'novolukoml',
        'krichev',
    ];

    public static function supportsSlug(string $slug): bool
    {
        return in_array($slug, self::ORDERED, true);
    }

    /** DQL CASE для сортировки: каталожные города первыми, в заданном порядке. */
    public static function priorityOrderDql(string $alias): string
    {
        $whenClauses = [];
        foreach (self::ORDERED as $index => $slug) {
            $whenClauses[] = sprintf("WHEN '%s' THEN %d", $slug, $index + 1);
        }

        return sprintf('CASE %s.slug %s ELSE 9999 END', $alias, implode(' ', $whenClauses));
    }
}
