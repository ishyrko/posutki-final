<?php

declare(strict_types=1);

namespace App\Domain\User\Service;

final class PhoneNumberNormalizer
{
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            throw new \InvalidArgumentException('Неверный номер телефона');
        }

        return '+' . $digits;
    }
}
