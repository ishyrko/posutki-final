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

    public function publicProperty(string $dealType, string $propertyType, int $propertyId, ?string $regionSlug = null): string
    {
        $typeSlug = self::PROPERTY_TYPE_TO_PATH_SLUG[$propertyType] ?? null;

        if ($typeSlug === null) {
            return $this->base() . '/' . $propertyId . '/';
        }

        $path = '/' . $typeSlug . '/' . $propertyId . '/';
        if ($regionSlug !== null && \in_array($regionSlug, self::URL_REGION_PREFIXES, true)) {
            $path = '/' . $regionSlug . $path;
        }

        return $this->base() . $path;
    }

    public function publicPropertyForListing(Property $property): string
    {
        return $this->publicProperty(
            $property->getDealType(),
            $property->getType(),
            $property->getId()->getValue(),
            $this->resolveUrlRegionSlug($property->getCityId()),
        );
    }

    private function resolveUrlRegionSlug(int $cityId): ?string
    {
        $city = $this->cityRepository->findById($cityId);
        if ($city === null) {
            return null;
        }

        $slug = $city->getRegionDistrict()?->getRegion()?->getSlug();
        if ($slug === null || $slug === 'minsk' || !\in_array($slug, self::URL_REGION_PREFIXES, true)) {
            return null;
        }

        return $slug;
    }
}
