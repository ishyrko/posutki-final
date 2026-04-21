<?php

declare(strict_types=1);

namespace App\Domain\Property\Enum;

enum SellerType: string
{
    case Individual = 'individual';
    case Business = 'business';

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
