<?php

declare(strict_types=1);

namespace App\Domain\Payment\Enum;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Successful = 'successful';
    case Failed = 'failed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает оплаты',
            self::Successful => 'Успешно',
            self::Failed => 'Отклонён',
            self::Expired => 'Истёк',
            self::Cancelled => 'Отменён',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Pending => false,
            self::Successful, self::Failed, self::Expired, self::Cancelled => true,
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
