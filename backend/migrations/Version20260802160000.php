<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260802160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add catalog_seo_text column to cities for per-city catalog SEO blocks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cities ADD catalog_seo_text LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cities DROP catalog_seo_text');
    }
}
