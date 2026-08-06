<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\ArticleHtmlNormalizer;
use App\Application\Service\ArticleTextSanitizer;
use App\Application\Service\LandmarkContentPersistNormalizer;
use App\Domain\Property\Entity\Landmark;
use PHPUnit\Framework\TestCase;

final class LandmarkContentPersistNormalizerTest extends TestCase
{
    public function testNormalizesTypographyInPlainAndHtmlFields(): void
    {
        $landmark = new Landmark(
            cityId: 1,
            name: 'Музей «Берестье»',
            slug: 'muzei-bereste',
            nameGenitive: 'музея «Берестье»',
        );
        $landmark->setShortDescription('Главный символ — сердце города.');
        $landmark->setDescription('<p>Текст «в кавычках» — продолжение.</p>');
        $landmark->setAddress('ул. Героев — 1');
        $landmark->setFacts([
            ['label' => 'Период', 'value' => 'XVI–XIX вв.'],
            ['label' => 'Открыт', 'value' => '2006 — год'],
        ]);
        $landmark->setGuestTips([
            'От метро «Восток» — около 5 минут.',
            '  ',
        ]);

        $normalizer = new LandmarkContentPersistNormalizer(
            new ArticleHtmlNormalizer(),
            new ArticleTextSanitizer(),
        );

        $normalizer->normalize($landmark);

        self::assertSame('Музей "Берестье"', $landmark->getName());
        self::assertSame('музея "Берестье"', $landmark->getNameGenitive());
        self::assertSame('Главный символ – сердце города.', $landmark->getShortDescription());
        self::assertSame('<p>Текст "в кавычках" – продолжение.</p>', $landmark->getDescription());
        self::assertSame('ул. Героев – 1', $landmark->getAddress());
        self::assertSame([
            ['label' => 'Период', 'value' => 'XVI–XIX вв.'],
            ['label' => 'Открыт', 'value' => '2006 – год'],
        ], $landmark->getFacts());
        self::assertSame(['От метро "Восток" – около 5 минут.'], $landmark->getGuestTips());
    }

    public function testClearsEmptyNullableTextFields(): void
    {
        $landmark = new Landmark(
            cityId: 1,
            name: 'Тест',
            slug: 'test',
            nameGenitive: 'теста',
        );
        $landmark->setShortDescription('   ');
        $landmark->setDescription('   ');
        $landmark->setAddress('   ');

        $normalizer = new LandmarkContentPersistNormalizer(
            new ArticleHtmlNormalizer(),
            new ArticleTextSanitizer(),
        );

        $normalizer->normalize($landmark);

        self::assertNull($landmark->getShortDescription());
        self::assertNull($landmark->getDescription());
        self::assertNull($landmark->getAddress());
    }
}
