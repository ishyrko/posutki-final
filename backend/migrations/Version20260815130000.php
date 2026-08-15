<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add city_room_catalog_contents table for per-city room-count catalog SEO';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE city_room_catalog_contents (
                id INT AUTO_INCREMENT NOT NULL,
                city_id INT NOT NULL,
                rooms_bucket SMALLINT NOT NULL,
                catalog_seo_text LONGTEXT DEFAULT NULL,
                catalog_faq JSON DEFAULT NULL,
                catalog_seo_visible TINYINT(1) DEFAULT 0 NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_CITY_ROOM_CATALOG_CITY_BUCKET (city_id, rooms_bucket),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE city_room_catalog_contents');
    }
}
