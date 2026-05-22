<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add external_calendar_urls JSON column to properties';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD external_calendar_urls JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP external_calendar_urls');
    }
}
