<?php

declare(strict_types=1);

namespace App\Domain\Property\Enum;

enum PlacementPurchaseKind: string
{
    case Level = 'level';
    case Boost = 'boost';

    public function label(): string
    {
        return match ($this) {
            self::Level => 'VIP-уровень',
            self::Boost => 'VIP-буст на 24 часа',
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
