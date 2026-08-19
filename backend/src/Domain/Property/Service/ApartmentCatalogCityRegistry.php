<?php

declare(strict_types=1);

namespace App\Domain\Property\Service;

use App\Domain\Property\Repository\CityRepositoryInterface;

final class ApartmentCatalogCityRegistry
{
    /** @var array{prefixSlugs: list<string>, catalogSlugs: list<string>}|null */
    private ?array $slugSets = null;

    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
    ) {
    }

    /** @return list<string> */
    public function getCatalogSlugs(): array
    {
        return $this->loadSlugSets()['catalogSlugs'];
    }

    /** @return list<string> */
    public function getPrefixSlugs(): array
    {
        return $this->loadSlugSets()['prefixSlugs'];
    }

    public function isCatalogSlug(string $slug): bool
    {
        return in_array($slug, $this->getCatalogSlugs(), true);
    }

    public function isPrefixSlug(string $slug): bool
    {
        return in_array($slug, $this->getPrefixSlugs(), true);
    }

    /** @return array{prefixSlugs: list<string>, catalogSlugs: list<string>} */
    public function getSlugSets(): array
    {
        return $this->loadSlugSets();
    }

    /** @return array{prefixSlugs: list<string>, catalogSlugs: list<string>} */
    private function loadSlugSets(): array
    {
        if ($this->slugSets !== null) {
            return $this->slugSets;
        }

        $prefixSlugs = [];
        $catalogSlugs = [];

        foreach ($this->cityRepository->findApartmentCatalog() as $city) {
            $slug = $city->getSlug();
            $catalogSlugs[] = $slug;
            if (!$city->isMain()) {
                $prefixSlugs[] = $slug;
            }
        }

        $this->slugSets = [
            'prefixSlugs' => $prefixSlugs,
            'catalogSlugs' => $catalogSlugs,
        ];

        return $this->slugSets;
    }
}
