<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

/**
 * Публичные маршруты Next.js — в синхроне с frontend/src/features/catalog/slugs.ts
 * (посуточно: в URL только kvartiry/doma и id).
 */
final readonly class FrontendUrlBuilder
{
    /** @var array<string, string> */
    private const PROPERTY_TYPE_TO_PATH_SLUG = [
        'apartment' => 'kvartiry',
        'house' => 'doma',
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
        $typeSlug = self::PROPERTY_TYPE_TO_PATH_SLUG[$propertyType] ?? null;

        if ($typeSlug === null) {
            return $this->base() . '/' . $propertyId . '/';
        }

        return $this->base() . '/' . $typeSlug . '/' . $propertyId . '/';
    }
}
