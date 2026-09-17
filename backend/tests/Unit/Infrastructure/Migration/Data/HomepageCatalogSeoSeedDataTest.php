<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Migration\Data;

use App\Infrastructure\Migration\Data\HomepageCatalogSeoSeedData;
use PHPUnit\Framework\TestCase;

final class HomepageCatalogSeoSeedDataTest extends TestCase
{
    /** @var list<string> */
    private const EXPECTED_SLUGS = [
        'beloozersk', 'bereza', 'borisov', 'bragin-gp', 'buda-koshelevo',
        'verhnedvinsk', 'vileyka', 'volozhin', 'gantsevichi', 'gorki',
        'dzerzhinsk', 'dokshitsy', 'drogichin', 'druzhnyy-p', 'dyatlovo',
        'elsk', 'zhabinka', 'zhitkovichi', 'zelva-gp', 'ivanovo',
        'ivatsevichi', 'ive', 'kalinkovichi', 'kamenets', 'kletsk',
        'klimovichi', 'kobrin', 'korma-gp', 'kostyukovichi', 'lelchitsy-gp',
        'lepel', 'luninets', 'lyuban', 'lyahovichi', 'marina-gorka',
        'mikashevichi', 'miory', 'mozyr', 'mosty', 'mstislavl',
        'myadel', 'naroch-kp', 'novogrudok', 'osipovichi', 'ostrovets',
        'oshmyany', 'petrikov', 'polotsk', 'postavy', 'pruzhany',
        'rechitsa', 'rogachev', 'svisloch', 'senno', 'slonim',
        'slutsk', 'smolevichi', 'soligorsk', 'stolbtsy', 'stolin',
        'fanipol', 'hoyniki', 'hotimsk-gp', 'chechersk', 'schuchin',
    ];

    public function testCoversEveryNewHomepageCityOnce(): void
    {
        $cities = HomepageCatalogSeoSeedData::cityContent();
        $rooms = HomepageCatalogSeoSeedData::roomContent();

        $slugs = array_keys($cities);
        sort($slugs);
        $expected = self::EXPECTED_SLUGS;
        sort($expected);

        self::assertSame($expected, $slugs);
        self::assertSame(array_keys($cities), array_keys($rooms));
    }

    public function testCityCopyHasTwoParagraphsAndFourFaqItems(): void
    {
        foreach (HomepageCatalogSeoSeedData::cityContent() as $slug => $entry) {
            self::assertSame(2, substr_count($entry['seo'], '<p>'), $slug);
            self::assertCount(4, $entry['faq'], $slug);
            foreach ($entry['faq'] as $item) {
                self::assertNotSame('', trim($item['question']), $slug);
                self::assertNotSame('', trim($item['answer']), $slug);
            }
        }
    }

    public function testRoomLandingsHaveCatalogLinkAndThreeFaqItems(): void
    {
        foreach (HomepageCatalogSeoSeedData::roomContent() as $slug => $buckets) {
            self::assertSame([1, 2, 3], array_keys($buckets), $slug);
            foreach ($buckets as $bucket => $entry) {
                $label = $slug.'#'.$bucket;
                self::assertSame(2, substr_count($entry['seo'], '<p>'), $label);
                self::assertStringContainsString(
                    sprintf('href="/%s/kvartiry/"', $slug),
                    $entry['seo'],
                    $label,
                );
                self::assertCount(3, $entry['faq'], $label);
            }
        }
    }

    public function testCopyDoesNotKeepDraftArtifacts(): void
    {
        $blob = json_encode(
            [HomepageCatalogSeoSeedData::cityContent(), HomepageCatalogSeoSeedData::roomContent()],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
        );

        self::assertStringNotContainsString('ок...', $blob);
        self::assertStringNotContainsString('Ивацевичского...', $blob);
        self::assertDoesNotMatchRegularExpression('/\s—\s/u', $blob);
    }
}
