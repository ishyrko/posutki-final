<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\CityRepositoryInterface;

/**
 * Публичные маршруты Next.js — в синхроне с frontend/src/features/catalog/slugs.ts
 * (посуточно: kvartiry/doma, id, для областных центров — префикс региона).
 */
final readonly class FrontendUrlBuilder
{
    /** @var array<string, string> */
    private const PROPERTY_TYPE_TO_PATH_SLUG = [
        'apartment' => 'kvartiry',
        'house' => 'doma',
    ];

    /** @var list<string> */
    private const URL_REGION_PREFIXES = ['brest', 'vitebsk', 'gomel', 'grodno', 'mogilev'];

    /** Города с префиксом в URL (только квартиры) — в синхроне с frontend CITY_PREFIX_SLUG_LIST (по алфавиту названий). */
    /** @var list<string> */
    private const CITY_PREFIX_SLUGS = [
        'baranovichi',
        'bobruysk',
        'volkovysk',
        'glubokoe',
        'zhlobin',
        'zhodino',
        'krichev',
        'logoysk',
        'molodechno',
        'nesvizh',
        'novolukoml',
        'novopolotsk',
        'orsha',
        'pinsk',
        'svetlogorsk',
        'smorgon',
    ];

    public function __construct(
        private string $frontendBaseUrl,
        private CityRepositoryInterface $cityRepository,
    ) {
    }

    public function base(): string
    {
        return rtrim($this->frontendBaseUrl, '/');
    }

    public function cabinet(): string
    {
        return $this->base() . '/kabinet/';
    }

    /** Публичная страница «Условия использования». */
    public function termsOfUse(): string
    {
        return $this->base() . '/usloviya-ispolzovaniya/';
    }

    public function myListings(): string
    {
        return $this->base() . '/kabinet/moi-obyavleniya/aktivnye/';
    }

    public function messages(): string
    {
        return $this->base() . '/kabinet/soobshcheniya/';
    }

    public function editProperty(int $propertyId): string
    {
        return $this->base() . '/kabinet/redaktirovat/' . $propertyId . '/';
    }

    public function placementPayment(int $purchaseId): string
    {
        return $this->base() . '/kabinet/oplata/' . $purchaseId . '/';
    }

    /** Same host as the site; nginx routes /admin to Symfony EasyAdmin. */
    public function admin(): string
    {
        return $this->base() . '/admin';
    }

    public function publicProperty(string $dealType, string $propertyType, int $propertyId, ?string $urlPrefix = null): string
    {
        $typeSlug = self::PROPERTY_TYPE_TO_PATH_SLUG[$propertyType] ?? null;

        if ($typeSlug === null) {
            return $this->base() . '/' . $propertyId . '/';
        }

        $path = '/' . $typeSlug . '/' . $propertyId . '/';
        if ($urlPrefix !== null && $this->isCatalogUrlPrefix($urlPrefix)) {
            $path = '/' . $urlPrefix . $path;
        }

        return $this->base() . $path;
    }

    public function publicPropertyForListing(Property $property): string
    {
        return $this->publicProperty(
            $property->getDealType(),
            $property->getType(),
            $property->getId()->getValue(),
            $this->resolveUrlPrefixSlug($property->getCityId(), $property->getType()),
        );
    }

    private function isCatalogUrlPrefix(string $slug): bool
    {
        return \in_array($slug, self::URL_REGION_PREFIXES, true)
            || \in_array($slug, self::CITY_PREFIX_SLUGS, true);
    }

    /**
     * Slug региона или города-префикса для URL объявления — в синхроне с
     * frontend propertyUrlRegionSlug().
     */
    private function resolveUrlPrefixSlug(int $cityId, string $propertyType): ?string
    {
        $city = $this->cityRepository->findById($cityId);
        if ($city === null) {
            return null;
        }

        $citySlug = $city->getSlug();

        if ($propertyType === 'apartment' && \in_array($citySlug, self::CITY_PREFIX_SLUGS, true)) {
            return $citySlug;
        }

        $regionSlug = $city->getRegionDistrict()?->getRegion()?->getSlug();
        if ($regionSlug === null || $regionSlug === 'minsk') {
            return null;
        }

        if (\in_array($regionSlug, self::URL_REGION_PREFIXES, true)) {
            return $regionSlug;
        }

        if (\in_array($citySlug, self::URL_REGION_PREFIXES, true)) {
            return $citySlug;
        }

        return null;
    }
}
