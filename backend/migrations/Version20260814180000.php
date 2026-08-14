<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add city microdistricts, residential complexes, catalog SEO fields, and property FK columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE city_microdistricts (
            id INT AUTO_INCREMENT NOT NULL,
            city_id INT NOT NULL,
            official_name VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            name_prepositional VARCHAR(255) NOT NULL,
            slug VARCHAR(255) DEFAULT NULL,
            catalog_seo_text LONGTEXT DEFAULT NULL,
            catalog_faq JSON DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_CITY_MICRODISTRICTS_CITY_ID (city_id),
            UNIQUE INDEX UNIQ_CITY_MICRODISTRICTS_CITY_OFFICIAL (city_id, official_name),
            UNIQUE INDEX UNIQ_CITY_MICRODISTRICTS_CITY_SLUG (city_id, slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE city_microdistricts ADD CONSTRAINT FK_CITY_MICRODISTRICTS_CITY_ID FOREIGN KEY (city_id) REFERENCES cities (id)');

        $this->addSql('CREATE TABLE residential_complexes (
            id INT AUTO_INCREMENT NOT NULL,
            city_id INT NOT NULL,
            official_name VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            name_prepositional VARCHAR(255) NOT NULL,
            slug VARCHAR(255) DEFAULT NULL,
            catalog_seo_text LONGTEXT DEFAULT NULL,
            catalog_faq JSON DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_RESIDENTIAL_COMPLEXES_CITY_ID (city_id),
            UNIQUE INDEX UNIQ_RESIDENTIAL_COMPLEXES_CITY_OFFICIAL (city_id, official_name),
            UNIQUE INDEX UNIQ_RESIDENTIAL_COMPLEXES_CITY_SLUG (city_id, slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE residential_complexes ADD CONSTRAINT FK_RESIDENTIAL_COMPLEXES_CITY_ID FOREIGN KEY (city_id) REFERENCES cities (id)');

        $this->addSql('ALTER TABLE city_districts ADD official_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE city_districts SET official_name = name WHERE official_name IS NULL');
        $this->addSql('ALTER TABLE city_districts MODIFY official_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE city_districts ADD name_prepositional VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE city_districts ADD catalog_seo_text LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE city_districts ADD catalog_faq JSON DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_CITY_DISTRICTS_CITY_NAME ON city_districts');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CITY_DISTRICTS_CITY_OFFICIAL ON city_districts (city_id, official_name)');

        $this->addSql('ALTER TABLE properties ADD city_microdistrict_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE properties ADD residential_complex_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_PROPERTIES_CITY_MICRODISTRICT_ID ON properties (city_microdistrict_id)');
        $this->addSql('CREATE INDEX IDX_PROPERTIES_RESIDENTIAL_COMPLEX_ID ON properties (residential_complex_id)');
        $this->addSql('ALTER TABLE properties ADD CONSTRAINT FK_PROPERTIES_CITY_MICRODISTRICT_ID FOREIGN KEY (city_microdistrict_id) REFERENCES city_microdistricts (id)');
        $this->addSql('ALTER TABLE properties ADD CONSTRAINT FK_PROPERTIES_RESIDENTIAL_COMPLEX_ID FOREIGN KEY (residential_complex_id) REFERENCES residential_complexes (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP FOREIGN KEY FK_PROPERTIES_CITY_MICRODISTRICT_ID');
        $this->addSql('ALTER TABLE properties DROP FOREIGN KEY FK_PROPERTIES_RESIDENTIAL_COMPLEX_ID');
        $this->addSql('DROP INDEX IDX_PROPERTIES_CITY_MICRODISTRICT_ID ON properties');
        $this->addSql('DROP INDEX IDX_PROPERTIES_RESIDENTIAL_COMPLEX_ID ON properties');
        $this->addSql('ALTER TABLE properties DROP city_microdistrict_id');
        $this->addSql('ALTER TABLE properties DROP residential_complex_id');

        $this->addSql('DROP INDEX UNIQ_CITY_DISTRICTS_CITY_OFFICIAL ON city_districts');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CITY_DISTRICTS_CITY_NAME ON city_districts (city_id, name)');
        $this->addSql('ALTER TABLE city_districts DROP catalog_faq');
        $this->addSql('ALTER TABLE city_districts DROP catalog_seo_text');
        $this->addSql('ALTER TABLE city_districts DROP name_prepositional');
        $this->addSql('ALTER TABLE city_districts DROP official_name');

        $this->addSql('ALTER TABLE city_microdistricts DROP FOREIGN KEY FK_CITY_MICRODISTRICTS_CITY_ID');
        $this->addSql('DROP TABLE city_microdistricts');
        $this->addSql('ALTER TABLE residential_complexes DROP FOREIGN KEY FK_RESIDENTIAL_COMPLEXES_CITY_ID');
        $this->addSql('DROP TABLE residential_complexes');
    }
}
