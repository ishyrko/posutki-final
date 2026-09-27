<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Migration\Data;

use App\Infrastructure\Migration\Data\EstateCatalogSeoSeedData;
use PHPUnit\Framework\TestCase;

final class EstateCatalogSeoSeedDataTest extends TestCase
{
    /** @var list<string> */
    private const EXPECTED_SLUGS = ['brest', 'gomel', 'grodno', 'minsk', 'mogilev', 'vitebsk'];

    public function testCoversBelarusAndEveryOblast(): void
    {
        $regions = EstateCatalogSeoSeedData::regions();
        $slugs = array_keys($regions);
        sort($slugs);

        self::assertSame(self::EXPECTED_SLUGS, $slugs);
        self::assertNotSame('', trim(EstateCatalogSeoSeedData::belarus()['seo']));
    }

    public function testEachCatalogHasTwoParagraphsAndFourDistinctFaqItems(): void
    {
        $entries = EstateCatalogSeoSeedData::regions();
        $entries['belarus'] = EstateCatalogSeoSeedData::belarus();

        $seoBodies = [];
        $questions = [];
        foreach ($entries as $key => $entry) {
            self::assertSame(2, substr_count($entry['seo'], '<p>'), $key);
            self::assertCount(4, $entry['faq'], $key);
            $seoBodies[] = $entry['seo'];
            foreach ($entry['faq'] as $item) {
                self::assertNotSame('', trim($item['question']), $key);
                self::assertNotSame('', trim($item['answer']), $key);
                $questions[] = $item['question'];
            }
        }

        self::assertSame($seoBodies, array_unique($seoBodies));
        self::assertSame($questions, array_unique($questions));
    }

    public function testOblastFactsStayOnTheRightPage(): void
    {
        $regions = EstateCatalogSeoSeedData::regions();

        self::assertStringContainsString('Несвижский замок', $regions['minsk']['seo']);
        self::assertStringContainsString('Гродненской', $regions['minsk']['seo']);
        self::assertStringContainsString('Каменюки', $regions['brest']['seo']);
        self::assertStringContainsString('243 км', $regions['vitebsk']['seo']);
        self::assertStringContainsString('Домжерицы', $regions['vitebsk']['seo']);
        self::assertStringContainsString('Лясковичи', $regions['gomel']['seo']);
        self::assertStringContainsString('радиационно-экологический', $regions['gomel']['seo']);
        self::assertStringContainsString('Кореличского', $regions['grodno']['seo']);
        self::assertStringContainsString('Немново', $regions['grodno']['seo']);
        self::assertStringContainsString('Осипович', $regions['mogilev']['seo']);
        self::assertStringContainsString('Домжерицы', $regions['mogilev']['seo']);
        self::assertStringNotContainsString('Каменюки', $regions['mogilev']['seo']);
        self::assertStringNotContainsString('Лясковичи', $regions['vitebsk']['seo']);
    }
}
