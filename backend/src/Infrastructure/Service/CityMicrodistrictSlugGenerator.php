<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Property\Repository\CityMicrodistrictRepositoryInterface;

final class CityMicrodistrictSlugGenerator
{
    public function __construct(
        private readonly SlugGenerator $slugGenerator,
        private readonly CityMicrodistrictRepositoryInterface $microdistrictRepository,
    ) {
    }

    public static function stripPrefix(string $name): string
    {
        $stripped = preg_replace('/^микрорайон\s+/ui', '', $name) ?? $name;
        $stripped = preg_replace('/^мкр\.?\s+/ui', '', $stripped) ?? $stripped;

        return trim($stripped);
    }

    public function generateSlug(int $cityId, string $name): string
    {
        $baseName = self::stripPrefix($name);
        $base = SlugGenerator::slugify($baseName !== '' ? $baseName : $name);

        if ($base === '') {
            $base = SlugGenerator::slugify($name);
        }

        if ($base === '') {
            $base = 'mikroraion';
        }

        return $this->slugGenerator->ensureUniqueByPredicate(
            $base,
            fn (string $candidate): bool => $this->microdistrictRepository->findByCityIdAndSlug($cityId, $candidate) !== null,
        );
    }
}
