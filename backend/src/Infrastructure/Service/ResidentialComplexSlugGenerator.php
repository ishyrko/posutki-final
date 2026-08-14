<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Property\Repository\ResidentialComplexRepositoryInterface;

final class ResidentialComplexSlugGenerator
{
    public function __construct(
        private readonly SlugGenerator $slugGenerator,
        private readonly ResidentialComplexRepositoryInterface $residentialComplexRepository,
    ) {
    }

    public static function stripPrefix(string $name): string
    {
        $stripped = preg_replace('/^жилой\s+комплекс\s+/ui', '', $name) ?? $name;
        $stripped = preg_replace('/^жилой\s+квартал\s+/ui', '', $stripped) ?? $stripped;
        $stripped = preg_replace('/^жк\s+/ui', '', $stripped) ?? $stripped;
        $stripped = preg_replace('/^экспериментальный\s+многофункциональный\s+комплекс\s+/ui', '', $stripped) ?? $stripped;

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
            $base = 'zhiloy-kompleks';
        }

        return $this->slugGenerator->ensureUniqueByPredicate(
            $base,
            fn (string $candidate): bool => $this->residentialComplexRepository->findByCityIdAndSlug($cityId, $candidate) !== null,
        );
    }
}
