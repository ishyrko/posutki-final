<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Application\Service\ArticleHtmlNormalizer;
use App\Application\Service\ArticleTextSanitizer;
use App\Application\Service\CatalogPlaceContentNormalizer;
use App\Infrastructure\Migration\Data\EstateCatalogSeoSeedData;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Estate catalog SEO for Belarus and every oblast. Editable later in admin.
 */
final class Version20260927120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fill estate catalog SEO text and FAQ for Belarus and all oblasts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE nationwide_house_catalog_contents (
                id INT AUTO_INCREMENT NOT NULL,
                catalog_seo_text LONGTEXT DEFAULT NULL,
                catalog_faq JSON DEFAULT NULL,
                catalog_seo_visible TINYINT(1) DEFAULT 0 NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $normalizer = $this->catalogContentNormalizer();
        $this->seedRegions($normalizer);
        $this->seedBelarus($normalizer);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DELETE c FROM region_catalog_contents c
            INNER JOIN regions r ON r.id = c.region_id
            WHERE r.slug IN ('minsk', 'brest', 'vitebsk', 'gomel', 'grodno', 'mogilev')
        SQL);
        $this->addSql('DROP TABLE nationwide_house_catalog_contents');
    }

    private function seedRegions(CatalogPlaceContentNormalizer $normalizer): void
    {
        foreach (EstateCatalogSeoSeedData::regions() as $slug => $entry) {
            $regionId = $this->connection->fetchOne(
                'SELECT id FROM regions WHERE slug = ?',
                [$slug],
            );
            if ($regionId === false) {
                throw new \RuntimeException(sprintf(
                    'Cannot fill estate catalog SEO: missing region %s.',
                    $slug,
                ));
            }

            $existingText = $this->connection->fetchOne(
                'SELECT catalog_seo_text FROM region_catalog_contents WHERE region_id = ?',
                [(int) $regionId],
            );
            if (is_string($existingText) && trim($existingText) !== '') {
                continue;
            }

            $seo = $normalizer->normalizeSeoText($entry['seo']);
            $faqJson = json_encode(
                $normalizer->normalizeFaq($entry['faq']),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );

            if ($existingText === false) {
                $this->addSql(
                    'INSERT INTO region_catalog_contents
                        (region_id, catalog_seo_text, catalog_faq, catalog_seo_visible, created_at)
                     VALUES (?, ?, ?, 1, NOW())',
                    [(int) $regionId, $seo, $faqJson],
                );
                continue;
            }

            $this->addSql(
                'UPDATE region_catalog_contents
                 SET catalog_seo_text = ?, catalog_faq = ?, catalog_seo_visible = 1
                 WHERE region_id = ?',
                [$seo, $faqJson, (int) $regionId],
            );
        }
    }

    private function seedBelarus(CatalogPlaceContentNormalizer $normalizer): void
    {
        $entry = EstateCatalogSeoSeedData::belarus();
        $seo = $normalizer->normalizeSeoText($entry['seo']);
        $faqJson = json_encode(
            $normalizer->normalizeFaq($entry['faq']),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        $this->addSql(
            'INSERT INTO nationwide_house_catalog_contents
                (catalog_seo_text, catalog_faq, catalog_seo_visible, created_at)
             VALUES (?, ?, 1, NOW())',
            [$seo, $faqJson],
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
