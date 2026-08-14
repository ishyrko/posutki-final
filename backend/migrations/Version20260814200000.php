<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add catalog_seo_visible toggle for city and catalog place SEO blocks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cities ADD catalog_seo_visible TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE city_districts ADD catalog_seo_visible TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE city_microdistricts ADD catalog_seo_visible TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE residential_complexes ADD catalog_seo_visible TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE residential_complexes DROP catalog_seo_visible');
        $this->addSql('ALTER TABLE city_microdistricts DROP catalog_seo_visible');
        $this->addSql('ALTER TABLE city_districts DROP catalog_seo_visible');
        $this->addSql('ALTER TABLE cities DROP catalog_seo_visible');
    }
}
