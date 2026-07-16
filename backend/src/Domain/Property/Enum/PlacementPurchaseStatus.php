<?php

declare(strict_types=1);

namespace App\Domain\Property\Enum;

enum PlacementPurchaseStatus: string
{
    case PendingPayment = 'pending_payment';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Ожидает оплаты',
            self::Active => 'Оплачено',
            self::Expired => 'Истекла',
            self::Cancelled => 'Отменена',
            self::Rejected => 'Отклонена',
            self::Superseded => 'Заменена',
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
