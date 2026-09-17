<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Application\Service\ArticleHtmlNormalizer;
use App\Application\Service\ArticleTextSanitizer;
use App\Application\Service\CatalogPlaceContentNormalizer;
use App\Infrastructure\Migration\Data\HomepageCatalogSeoSeedData;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Catalog SEO copy for homepage cities that were enabled without texts.
 *
 * Geographic claims: belarus.by / belta.by (Ostrovets NPP 18 km, Gudogai),
 * novtour.by / mickiewicz.by (Novogrudok, no railway, Novoelnya ~30 km, Svityaz ~20 km, Mir ~50 km),
 * npbp.by / bp21.org.by (Kamenets tower vs Kamenyuki ~20 km),
 * minoblturism.gov.by (Dzerzhinskaya Gora 345 m at Skirmantovo ~15 km),
 * slutskiepoyasa.by (Slutsk, Bogdanovicha 8), kobrin.museum.by / suvorovmuseum.by,
 * borisov.museum.by (Batarei 1812), wikipedia / city portals for stations
 * (Koydanovo, Pukhovichi, Polotsk, Kalinkovichi vs Mozyr, Pogodino for Gorki).
 */
final class Version20260917220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fill catalog SEO text and FAQ for new homepage cities, including room landings 1–3';
    }

    public function up(Schema $schema): void
    {
        $content = HomepageCatalogSeoSeedData::cityContent();
        $roomContent = HomepageCatalogSeoSeedData::roomContent();
        $existingSlugs = $this->connection->fetchFirstColumn(
            'SELECT slug FROM cities WHERE slug IN (?)',
            [array_keys($content)],
            [\Doctrine\DBAL\ArrayParameterType::STRING],
        );
        $missingSlugs = array_values(array_diff(array_keys($content), $existingSlugs));

        if ($missingSlugs !== []) {
            throw new \RuntimeException(sprintf(
                'Cannot fill city catalog content: missing cities with slugs %s.',
                implode(', ', $missingSlugs),
            ));
        }

        $normalizer = $this->catalogContentNormalizer();

        foreach ($content as $slug => $cityContent) {
            $cityId = $this->connection->fetchOne(
                'SELECT id FROM cities WHERE slug = ?',
                [$slug],
            );
            if ($cityId === false) {
                throw new \RuntimeException(sprintf(
                    'Cannot fill catalog SEO: missing city %s.',
                    $slug,
                ));
            }

            $seo = $normalizer->normalizeSeoText($cityContent['seo']);
            $faqJson = json_encode(
                $normalizer->normalizeFaq($cityContent['faq']),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );

            $this->addSql(
                'UPDATE cities
                 SET catalog_seo_text = ?, catalog_faq = ?, catalog_seo_visible = 1
                 WHERE slug = ?',
                [$seo, $faqJson, $slug],
            );

            if (!isset($roomContent[$slug])) {
                throw new \RuntimeException(sprintf(
                    'Room catalog seed is missing for %s.',
                    $slug,
                ));
            }

            foreach ($roomContent[$slug] as $bucket => $entry) {
                $roomSeo = $normalizer->normalizeSeoText($entry['seo']);
                $roomFaqJson = json_encode(
                    $normalizer->normalizeFaq($entry['faq']),
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                );

                $rowExists = $this->connection->fetchOne(
                    'SELECT 1 FROM city_room_catalog_contents
                     WHERE city_id = ? AND rooms_bucket = ?',
                    [(int) $cityId, $bucket],
                );

                if ($rowExists !== false) {
                    $this->addSql(
                        'UPDATE city_room_catalog_contents
                         SET catalog_seo_text = ?, catalog_faq = ?, catalog_seo_visible = 1
                         WHERE city_id = ? AND rooms_bucket = ?',
                        [$roomSeo, $roomFaqJson, (int) $cityId, $bucket],
                    );
                } else {
                    $this->addSql(
                        'INSERT INTO city_room_catalog_contents
                            (city_id, rooms_bucket, catalog_seo_text, catalog_faq, catalog_seo_visible, created_at)
                         VALUES (?, ?, ?, ?, 1, NOW())',
                        [(int) $cityId, $bucket, $roomSeo, $roomFaqJson],
                    );
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Homepage city catalog SEO text, FAQ and visibility cannot be restored safely.',
        );
    }

    private function catalogContentNormalizer(): CatalogPlaceContentNormalizer
    {
        return new CatalogPlaceContentNormalizer(
            new ArticleHtmlNormalizer(),
            new ArticleTextSanitizer(),
        );
    }
}
