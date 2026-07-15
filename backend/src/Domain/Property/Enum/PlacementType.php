<?php

declare(strict_types=1);

namespace App\Domain\Property\Enum;

enum PlacementType: string
{
    case Special = 'special';
    case Standard = 'standard';
    case Free = 'free';

    public function label(): string
    {
        return match ($this) {
            self::Special => 'Спецразмещение',
            self::Standard => 'Стандартное размещение',
            self::Free => 'Бесплатное',
        };
    }

    /** Higher = shown first in catalog. */
    public function sortWeight(): int
    {
        return match ($this) {
            self::Special => 2,
            self::Standard => 1,
            self::Free => 0,
        };
    }

    /** @return array<string, string> */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->label()] = $case->value;
        }

        return $choices;
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
