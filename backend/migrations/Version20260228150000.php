<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bathrooms and year_built to properties';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD bathrooms INT DEFAULT NULL, ADD year_built INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP bathrooms, DROP year_built');
    }
}
