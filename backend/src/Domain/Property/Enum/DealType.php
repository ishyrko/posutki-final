<?php

declare(strict_types=1);

namespace App\Domain\Property\Enum;

enum DealType: string
{
    case Daily = 'daily';

    public function label(): string
    {
        return 'Посуточно';
    }

    /** @return array<string, string> */
    public static function choices(): array
    {
        return ['Посуточно' => self::Daily->value];
    }

    /** @return string[] */
    public static function values(): array
    {
        return [self::Daily->value];
    }
}
