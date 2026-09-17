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
            static fn(City $left, City $right): int => self::settlementRank($left->getName()) <=> self::settlementRank($right->getName()),
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
            return 2;
        }

        return 3;
    }
}
