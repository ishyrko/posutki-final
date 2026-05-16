<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260516120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment_methods JSON column to properties';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD payment_methods JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP COLUMN payment_methods');
    }
}
