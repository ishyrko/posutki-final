<?php

declare(strict_types=1);

namespace App\Infrastructure\Import;

use App\Domain\Property\Entity\City;

final class PartnerCityMatcher
{
    /**
     * @param list<City> $candidates
     */
    public static function pick(string $query, array $candidates): ?City
    {
        $normalized = self::coreName($query);
        if ($normalized === '') {
            return null;
        }

        $exact = [];
        foreach ($candidates as $candidate) {
            if (self::coreName($candidate->getName()) === $normalized) {
                $exact[] = $candidate;
            }
        }

        if ($exact === []) {
            return null;
        }

        usort(
            $exact,
            static function (City $left, City $right): int {
                $rank = self::settlementRank($left->getName()) <=> self::settlementRank($right->getName());
                if ($rank !== 0) {
                    return $rank;
                }

                return self::cityPreference($right) <=> self::cityPreference($left);
            },
        );

        return $exact[0];
    }

    public static function coreName(string $value): string
    {
        $folded = self::foldYo($value);
        // Geo suffixes: «г.», «д.», «г.п.», «аг.», «р.п.», «к.п.», «х.».
        $stripped = preg_replace(
            '/\s+(?:г\.п|р\.п|к\.п|гп|аг|рп|кп|г|д|п|с|х)\.?\s*$/u',
            '',
            $folded,
        ) ?? $folded;

        return trim($stripped);
    }

    public static function foldYo(string $value): string
    {
        return mb_strtolower(str_replace(['ё', 'Ё'], ['е', 'Е'], $value));
    }

    public static function settlementRank(string $name): int
    {
        $folded = self::foldYo($name);
        if (preg_match('/\sг\.п\.?\s*$/u', $folded) === 1) {
            return 1;
        }
        if (preg_match('/\sг\.?\s*$/u', $folded) === 1) {
            return 0;
        }
        if (preg_match('/\s(?:аг|а\.г)\.?\s*$/u', $folded) === 1) {
            return 3;
        }
        if (preg_match('/\s(?:р\.п|к\.п|д|п|с|х)\.?\s*$/u', $folded) === 1) {
            return 4;
        }

        // «Гомель», «Речица», «Старые Дороги» — город без суффикса, не агрогородок.
        return 0;
    }

    private static function cityPreference(City $city): int
    {
        return ($city->isMain() ? 4 : 0)
            + ($city->isApartmentCatalog() ? 2 : 0)
            + ($city->isListingSuggested() ? 1 : 0);
    }
}
