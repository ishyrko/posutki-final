<?php

declare(strict_types=1);

namespace App\Domain\Property\Enum;

enum PropertyType: string
{
    case Apartment   = 'apartment';
    case House       = 'house';
    case Room        = 'room';
    case Land        = 'land';
    case Garage      = 'garage';
    case Parking     = 'parking';
    case Dacha       = 'dacha';
    case Office      = 'office';
    case Retail      = 'retail';
    case Warehouse   = 'warehouse';

    public function label(): string
    {
        return match ($this) {
            self::Apartment   => 'Квартира',
            self::House       => 'Дом',
            self::Room        => 'Комната',
            self::Land        => 'Участок',
            self::Garage      => 'Гараж',
            self::Parking     => 'Машиноместо',
            self::Dacha       => 'Дача',
            self::Office      => 'Офис',
            self::Retail      => 'Торговое помещение',
            self::Warehouse   => 'Склад',
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
