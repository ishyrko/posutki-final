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

/** FAQ in DB was stale: Version20260815130500 ran before FAQ refresh was added. */
final class Version20260815130600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refresh city room catalog FAQ with city names from seed data';
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
                    'Cannot update room catalog FAQ: missing city %s.',
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

                $faqJson = json_encode(
                    $normalizer->normalizeFaq($content[$citySlug][$bucket]['faq']),
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                );

                $this->addSql(
                    'UPDATE city_room_catalog_contents
                     SET catalog_faq = ?
                     WHERE city_id = ? AND rooms_bucket = ?',
                    [$faqJson, (int) $cityId, $bucket],
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Previous room catalog FAQ cannot be restored safely.',
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
