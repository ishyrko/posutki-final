<?php

declare(strict_types=1);

namespace App\Infrastructure\Migration\Data\HomepageCatalogSeo;

trait AssemblesRoomCatalogSeo
{
    /**
     * @return array<int, array{seo: string, faq: list<array{question: string, answer: string}>}>
     */
    private static function roomsFor(string $slug, string $seo1, string $seo2, string $seo3): array
    {
        $faq = static::roomFaq()[$slug] ?? null;
        if ($faq === null) {
            throw new \LogicException(sprintf('Room FAQ is missing for %s.', $slug));
        }

        return [
            1 => ['seo' => $seo1, 'faq' => $faq[1]],
            2 => ['seo' => $seo2, 'faq' => $faq[2]],
            3 => ['seo' => $seo3, 'faq' => $faq[3]],
        ];
    }

    /**
     * @return array<string, array<int, list<array{question: string, answer: string}>>>
     */
    abstract protected static function roomFaq(): array;
}
