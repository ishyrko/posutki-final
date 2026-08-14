<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\ArticleHtmlNormalizer;
use App\Application\Service\ArticleTextSanitizer;
use App\Application\Service\CatalogPlaceContentNormalizer;
use App\Domain\Property\Entity\City;
use PHPUnit\Framework\TestCase;

final class CatalogPlaceContentNormalizerTest extends TestCase
{
    public function testNormalizesSeoHtmlAndFaqTypography(): void
    {
        $city = new City();
        $city->setCatalogSeoText('<p>Текст «в кавычках» — продолжение.</p>');
        $city->setCatalogFaq([
            [
                'question' => 'Есть ли метро «Восток»?',
                'answer' => 'Да — станция рядом.',
            ],
            [
                'question' => '   ',
                'answer' => 'Ответ',
            ],
        ]);

        $normalizer = new CatalogPlaceContentNormalizer(
            new ArticleHtmlNormalizer(),
            new ArticleTextSanitizer(),
        );

        $normalizer->normalizeEntity($city);

        self::assertSame('<p>Текст "в кавычках" – продолжение.</p>', $city->getCatalogSeoText());
        self::assertSame([
            [
                'question' => 'Есть ли метро "Восток"?',
                'answer' => 'Да – станция рядом.',
            ],
        ], $city->getCatalogFaq());
    }

    public function testClearsEmptySeoTextAndFaq(): void
    {
        $city = new City();
        $city->setCatalogSeoText('   ');
        $city->setCatalogFaq([]);

        $normalizer = new CatalogPlaceContentNormalizer(
            new ArticleHtmlNormalizer(),
            new ArticleTextSanitizer(),
        );

        $normalizer->normalizeEntity($city);

        self::assertNull($city->getCatalogSeoText());
        self::assertNull($city->getCatalogFaq());
    }
}
