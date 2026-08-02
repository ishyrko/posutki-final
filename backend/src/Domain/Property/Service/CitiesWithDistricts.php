<?php

declare(strict_types=1);

namespace App\Domain\Property\Service;

/**
 * Belarusian cities that are administratively divided into city districts (районы в городе).
 */
final class CitiesWithDistricts
{
    /** @var list<string> */
    public const SLUGS = [
        'minsk',
        'brest',
        'vitebsk',
        'gomel',
        'grodno',
        'mogilev',
        'bobruysk',
    ];

    public static function supportsSlug(string $slug): bool
    {
        return in_array($slug, self::SLUGS, true);
    }
}
