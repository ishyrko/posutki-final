<?php

declare(strict_types=1);

namespace App\Domain\Property\Enum;

enum DealType: string
{
    case Rent  = 'rent';
    case Sale  = 'sale';
    case Daily = 'daily';

    public function label(): string
    {
        return match ($this) {
            self::Rent  => 'Аренда',
            self::Sale  => 'Продажа',
            self::Daily => 'Посуточно',
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
