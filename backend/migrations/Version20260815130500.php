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

/** Remove self-referencing internal links from room landing SEO copy. */
final class Version20260815130500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop self-referencing room landing links from city room SEO text';
    }

    public function up(Schema $schema): void
    {
        $content = CityRoomCatalogSeoSeedData::content();
        $normalizer = $this->catalogContentNormalizer();

        foreach (CatalogApartmentCitySlugs::ORDERED as $citySlug) {
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

                $this->addSql(
                    'UPDATE city_room_catalog_contents
                     SET catalog_seo_text = ?
                     WHERE city_id = ? AND rooms_bucket = ?',
                    [$seo, (int) $cityId, $bucket],
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Room landing copy with self-links cannot be restored safely.',
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
