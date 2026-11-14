<?php

declare(strict_types=1);

namespace App\Domain\Payment\Enum;

enum PaymentMethod: string
{
    case Card = 'card';
    case Epos = 'epos';

    public function label(): string
    {
        return match ($this) {
            self::Card => 'Банковская карта',
            self::Epos => 'ЕРИП (E-POS)',
        };
    }
}
