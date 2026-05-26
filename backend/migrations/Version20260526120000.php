<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add custom street_name column to properties';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD street_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP street_name');
    }
}
