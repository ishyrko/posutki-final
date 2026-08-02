<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260802140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add slug column to city_districts for SEO-friendly district catalog URLs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE city_districts ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CITY_DISTRICTS_CITY_SLUG ON city_districts (city_id, slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_CITY_DISTRICTS_CITY_SLUG ON city_districts');
        $this->addSql('ALTER TABLE city_districts DROP slug');
    }
}
