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
 * Drop four-room SEO rows and refresh 1–3 room seed copy without 4+ landing links.
 */
final class Version20260815130200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove room-bucket 4 SEO rows and refresh buckets 1–3 seed content';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM city_room_catalog_contents WHERE rooms_bucket = 4');

        $content = CityRoomCatalogSeoSeedData::content();
        $normalizer = $this->catalogContentNormalizer();

        foreach (CatalogApartmentCitySlugs::ORDERED as $citySlug) {
            $cityId = $this->connection->fetchOne(
                'SELECT id FROM cities WHERE slug = ?',
                [$citySlug],
            );
            if ($cityId === false) {
                continue;
            }

            for ($bucket = 1; $bucket <= 3; ++$bucket) {
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

                $updated = $this->connection->executeStatement(
                    'UPDATE city_room_catalog_contents
                     SET catalog_seo_text = ?, catalog_faq = ?
                     WHERE city_id = ? AND rooms_bucket = ?',
                    [$seo, $faqJson, (int) $cityId, $bucket],
                );

                if ($updated === 0) {
                    $this->connection->executeStatement(
                        'INSERT INTO city_room_catalog_contents
                            (city_id, rooms_bucket, catalog_seo_text, catalog_faq, catalog_seo_visible, created_at)
                         VALUES (?, ?, ?, ?, 0, NOW())',
                        [(int) $cityId, $bucket, $seo, $faqJson],
                    );
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Removed room-bucket 4 SEO rows and refreshed seed copy cannot be restored.',
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
