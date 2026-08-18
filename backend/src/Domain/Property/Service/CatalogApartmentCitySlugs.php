<?php

declare(strict_types=1);

namespace App\Domain\Property\Service;

/**
 * Slugs городов с посуточным каталогом квартир — в синхроне с frontend
 * CITY_PREFIX_SLUG_LIST / HOME_CITY_FILTERS.
 * Областные центры — фиксированный порядок; мелкие города — по алфавиту названий.
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
        'bobruysk',
        'volkovysk',
        'glubokoe',
        'zhlobin',
        'zhodino',
        'krichev',
        'logoysk',
        'molodechno',
        'nesvizh',
        'novolukoml',
        'novopolotsk',
        'orsha',
        'pinsk',
        'svetlogorsk',
        'smorgon',
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
