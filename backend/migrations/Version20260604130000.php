<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add archived_at to properties for 30-day delete cooldown';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD archived_at DATETIME(6) DEFAULT NULL AFTER published_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP archived_at');
    }
}
