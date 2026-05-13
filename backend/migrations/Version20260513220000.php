<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add weekend_price_negotiable boolean column to properties table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD weekend_price_negotiable BOOLEAN NOT NULL DEFAULT FALSE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP COLUMN weekend_price_negotiable');
    }
}
