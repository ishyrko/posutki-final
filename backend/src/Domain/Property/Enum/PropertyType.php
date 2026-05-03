<?php

declare(strict_types=1);

namespace App\Domain\Property\Enum;

enum PropertyType: string
{
    case Apartment = 'apartment';
    case House     = 'house';

    public function label(): string
    {
        return match ($this) {
            self::Apartment => 'Квартира',
            self::House     => 'Дом',
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
