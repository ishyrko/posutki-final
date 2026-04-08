<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

/**
 * Public routes for the Next.js frontend — must stay in sync with frontend/src/features/catalog/slugs.ts
 * (buildPropertyUrl, cabinet paths).
 */
final readonly class FrontendUrlBuilder
{
    /** @var array<string, string> */
    private const DEAL_TYPE_TO_PATH_SLUG = [
        'rent' => 'arenda',
        'sale' => 'prodazha',
        'daily' => 'posutochno',
    ];

    /** @var array<string, string> */
    private const PROPERTY_TYPE_TO_PATH_SLUG = [
        'apartment' => 'kvartiry',
        'house' => 'doma',
        'room' => 'komnaty',
        'land' => 'uchastki',
        'garage' => 'garazhi',
        'parking' => 'mashinomesta',
        'dacha' => 'dachi',
        'office' => 'ofisy',
        'retail' => 'torgovye',
        'warehouse' => 'sklady',
    ];

    public function __construct(
        private string $frontendBaseUrl,
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

    public function editProperty(int $propertyId): string
    {
        return $this->base() . '/kabinet/redaktirovat/' . $propertyId . '/';
    }

    /** Same host as the site; nginx routes /admin to Symfony EasyAdmin. */
    public function admin(): string
    {
        return $this->base() . '/admin';
    }

    public function publicProperty(string $dealType, string $propertyType, int $propertyId): string
    {
        $dealSlug = self::DEAL_TYPE_TO_PATH_SLUG[$dealType] ?? null;
        $typeSlug = self::PROPERTY_TYPE_TO_PATH_SLUG[$propertyType] ?? null;

        if ($dealSlug === null || $typeSlug === null) {
            return $this->base() . '/' . $propertyId . '/';
        }

        return $this->base() . '/' . $dealSlug . '/' . $typeSlug . '/' . $propertyId . '/';
    }
}
