<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Property\Repository\CityDistrictRepositoryInterface;

final class CityDistrictSlugGenerator
{
    public function __construct(
        private readonly SlugGenerator $slugGenerator,
        private readonly CityDistrictRepositoryInterface $cityDistrictRepository,
    ) {
    }

    /**
     * Remove administrative suffix «район» (and inflections) before slug transliteration.
     */
    public static function stripDistrictSuffix(string $name): string
    {
        $stripped = preg_replace('/\bрайон(а|е|ом|у)?\b/ui', '', $name) ?? $name;
        $normalized = preg_replace('/\s+/u', ' ', $stripped) ?? $stripped;

        return trim($normalized);
    }

    public function generateSlug(int $cityId, string $name): string
    {
        $baseName = self::stripDistrictSuffix($name);
        $base = SlugGenerator::slugify($baseName !== '' ? $baseName : $name);

        if ($base === '') {
            $base = SlugGenerator::slugify($name);
        }

        if ($base === '') {
            $base = 'district';
        }

        return $this->slugGenerator->ensureUniqueByPredicate(
            $base,
            fn (string $candidate): bool => $this->cityDistrictRepository->findByCityIdAndSlug($cityId, $candidate) !== null,
        );
    }
}
