<?php

declare(strict_types=1);

namespace App\Domain\Property\Validation;

use App\Domain\Shared\Exception\DomainException;

final class PaymentMethodsValidator
{
    private const ALLOWED = [
        'cash',
        'card',
        'bank_transfer',
    ];

    /**
     * @param array<int, mixed>|null $paymentMethods
     */
    public static function assertValid(?array $paymentMethods): void
    {
        if ($paymentMethods === null || $paymentMethods === []) {
            return;
        }

        $unknown = array_diff($paymentMethods, self::ALLOWED);
        if ($unknown !== []) {
            throw new DomainException(
                'Неизвестные способы оплаты: ' . implode(', ', $unknown)
            );
        }
    }

    /**
     * @return string[]
     */
    public static function allowed(): array
    {
        return self::ALLOWED;
    }
}
