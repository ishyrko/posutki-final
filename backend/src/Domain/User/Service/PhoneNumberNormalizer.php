<?php

declare(strict_types=1);

namespace App\Domain\User\Service;

final class PhoneNumberNormalizer
{
    private const COUNTRY_CODE = '375';
    private const NATIONAL_LENGTH = 9;
    private const INTERNATIONAL_LENGTH = 12;

    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            throw new \InvalidArgumentException('Неверный номер телефона');
        }

        $national = self::extractNationalDigits($digits);
        if ($national === null) {
            throw new \InvalidArgumentException('Неверный номер телефона');
        }

        return '+' . self::COUNTRY_CODE . $national;
    }

    private static function extractNationalDigits(string $digits): ?string
    {
        if (str_starts_with($digits, self::COUNTRY_CODE)) {
            if (strlen($digits) !== self::INTERNATIONAL_LENGTH) {
                return null;
            }

            return substr($digits, 3);
        }

        if (str_starts_with($digits, '80') && strlen($digits) === 11) {
            return substr($digits, 2);
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return substr($digits, 1);
        }

        if (strlen($digits) === self::NATIONAL_LENGTH) {
            return $digits;
        }

        return null;
    }
}
