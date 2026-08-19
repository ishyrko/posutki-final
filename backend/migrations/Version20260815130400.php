<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Application\Service\ArticleHtmlNormalizer;
use App\Application\Service\ArticleTextSanitizer;
use App\Application\Service\CatalogPlaceContentNormalizer;
use App\Infrastructure\Migration\Data\CityRoomCatalogSeoSeedData;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * @deprecated Историческая миграция — уже применена на всех окружениях, не изменять.
 *             Список городов каталога теперь в cities.is_apartment_catalog (Version20260819120000).
 */
final class Version20260815130400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace generated room landing copy with revised editorial SEO text and FAQ';
    }

    public function up(Schema $schema): void
    {
        $content = CityRoomCatalogSeoSeedData::content();
        $normalizer = $this->catalogContentNormalizer();

        foreach (array_keys($content) as $citySlug) {
            $cityId = $this->connection->fetchOne(
                'SELECT id FROM cities WHERE slug = ?',
                [$citySlug],
            );
            if ($cityId === false) {
                throw new \RuntimeException(sprintf(
                    'Cannot update room catalog SEO: missing city %s.',
                    $citySlug,
                ));
            }

            foreach ([1, 2, 3] as $bucket) {
                if (!isset($content[$citySlug][$bucket])) {
                    throw new \RuntimeException(sprintf(
                        'CityRoomCatalogSeoSeedData is missing bucket %d for %s.',
                        $bucket,
                        $citySlug,
                    ));
                }

                $entry = $content[$citySlug][$bucket];
                $seo = $normalizer->normalizeSeoText($entry['seo']);
                $faqJson = json_encode(
                    $normalizer->normalizeFaq($entry['faq']),
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                );

                $rowExists = $this->connection->fetchOne(
                    'SELECT 1 FROM city_room_catalog_contents
                     WHERE city_id = ? AND rooms_bucket = ?',
                    [(int) $cityId, $bucket],
                );
                if ($rowExists === false) {
                    throw new \RuntimeException(sprintf(
                        'Cannot update missing room catalog SEO row: %s, bucket %d.',
                        $citySlug,
                        $bucket,
                    ));
                }

                $this->addSql(
                    'UPDATE city_room_catalog_contents
                     SET catalog_seo_text = ?, catalog_faq = ?
                     WHERE city_id = ? AND rooms_bucket = ?',
                    [$seo, $faqJson, (int) $cityId, $bucket],
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Previous room landing copy cannot be restored safely.',
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
