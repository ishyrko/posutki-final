<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Property\Entity\City;

final class CityNameGenitiveResolver
{
    /** @var array<string, string>|null */
    private static ?array $overridesBySlug = null;

    /** @var array<string, true> */
    private const INDECCLINABLE = [
        'Березино' => true,
        'Дубровно' => true,
        'Жодино' => true,
        'Иваново' => true,
        'Коссово' => true,
        'Молодечно' => true,
        'Сенно' => true,
        'Гродно' => true,
    ];

    public function resolve(City $city): string
    {
        $manual = $city->getNameGenitive();
        if ($manual !== null && trim($manual) !== '') {
            return trim($manual);
        }

        $slug = $city->getSlug();
        if ($slug !== null && $slug !== '') {
            $overrides = $this->getOverridesBySlug();
            if (isset($overrides[$slug])) {
                return $overrides[$slug];
            }
        }

        return $this->declineName($this->stripAdministrativeSuffix($city->getName()));
    }

    public function resolveFromName(string $name, ?string $slug = null): string
    {
        if ($slug !== null && $slug !== '') {
            $overrides = $this->getOverridesBySlug();
            if (isset($overrides[$slug])) {
                return $overrides[$slug];
            }
        }

        return $this->declineName($this->stripAdministrativeSuffix($name));
    }

    private function stripAdministrativeSuffix(string $name): string
    {
        $name = trim($name);

        foreach ([' г.', ' г.п.', ' р.п.', ' д.'] as $suffix) {
            if (str_ends_with($name, $suffix)) {
                return trim(substr($name, 0, -strlen($suffix)));
            }
        }

        return $name;
    }

    private function declineName(string $name): string
    {
        if ($name === '') {
            return $name;
        }

        if (isset(self::INDECCLINABLE[$name])) {
            return $name;
        }

        if (preg_match('/ичи$/u', $name) === 1) {
            return mb_substr($name, 0, -1) . 'ей';
        }

        if (preg_match('/ы$/u', $name) === 1) {
            return mb_substr($name, 0, -1) . 'ов';
        }

        if (preg_match('/ень$/u', $name) === 1) {
            return mb_substr($name, 0, -1) . 'я';
        }

        if (preg_match('/ель$/u', $name) === 1) {
            return mb_substr($name, 0, -2) . 'еля';
        }

        if (preg_match('/ск$/u', $name) === 1 || preg_match('/ов$/u', $name) === 1 || preg_match('/ин$/u', $name) === 1) {
            return $name . 'а';
        }

        if (preg_match('/ец$/u', $name) === 1) {
            return mb_substr($name, 0, -2) . 'ца';
        }

        if (str_contains($name, ' ') || str_contains($name, '-')) {
            return $this->declineCompoundName($name);
        }

        return $name;
    }

    private function declineCompoundName(string $name): string
    {
        $parts = preg_split('/(\s+|-)/u', $name, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $name;
        }

        $result = '';
        foreach ($parts as $part) {
            if ($part === ' ' || $part === '-') {
                $result .= $part;
                continue;
            }

            $result .= $this->declineName($part);
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private function getOverridesBySlug(): array
    {
        if (self::$overridesBySlug !== null) {
            return self::$overridesBySlug;
        }

        $path = dirname(__DIR__, 3) . '/migrations/data/city_seed_data.php';
        if (!is_file($path)) {
            self::$overridesBySlug = [];

            return self::$overridesBySlug;
        }

        /** @var array<string, array{genitive: string}> $seed */
        $seed = require $path;
        self::$overridesBySlug = array_map(
            static fn (array $row): string => $row['genitive'],
            $seed,
        );

        return self::$overridesBySlug;
    }
}
