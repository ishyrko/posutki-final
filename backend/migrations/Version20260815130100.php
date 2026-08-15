<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Application\Service\ArticleHtmlNormalizer;
use App\Application\Service\ArticleTextSanitizer;
use App\Application\Service\CatalogPlaceContentNormalizer;
use App\Domain\Property\Service\CatalogApartmentCitySlugs;
use App\Infrastructure\Migration\Data\CityRoomCatalogSeoSeedData;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Seed hidden catalog SEO text and FAQ for city × room-count landing pages.
 */
final class Version20260815130100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fill hidden catalog SEO text and FAQ for city room-count catalog pages (80 entries)';
    }

    public function up(Schema $schema): void
    {
        $content = CityRoomCatalogSeoSeedData::content();
        $citySlugs = CatalogApartmentCitySlugs::ORDERED;
        $missingSlugs = array_values(array_diff($citySlugs, array_keys($content)));

        if ($missingSlugs !== []) {
            throw new \RuntimeException(sprintf(
                'CityRoomCatalogSeoSeedData is missing slugs: %s.',
                implode(', ', $missingSlugs),
            ));
        }

        $normalizer = $this->catalogContentNormalizer();

        foreach ($citySlugs as $citySlug) {
            $cityId = $this->connection->fetchOne(
                'SELECT id FROM cities WHERE slug = ?',
                [$citySlug],
            );
            if ($cityId === false) {
                throw new \RuntimeException(sprintf('Cannot seed room catalog SEO: missing city %s.', $citySlug));
            }

            for ($bucket = 1; $bucket <= 4; ++$bucket) {
                if (!isset($content[$citySlug][$bucket])) {
                    throw new \RuntimeException(sprintf(
                        'CityRoomCatalogSeoSeedData is missing bucket %d for %s.',
                        $bucket,
                        $citySlug,
                    ));
                }

                $entry = $content[$citySlug][$bucket];
                $seo = $normalizer->normalizeSeoText($entry['seo']);
                $faq = $normalizer->normalizeFaq($entry['faq']);
                $faqJson = json_encode(
                    $faq,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                );

                $this->addSql(
                    'INSERT INTO city_room_catalog_contents
                        (city_id, rooms_bucket, catalog_seo_text, catalog_faq, catalog_seo_visible, created_at)
                     VALUES (?, ?, ?, ?, 0, NOW())',
                    [(int) $cityId, $bucket, $seo, $faqJson],
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Seeded room catalog SEO rows cannot be restored safely after deletion.',
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
