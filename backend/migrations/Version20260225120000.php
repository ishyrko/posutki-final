<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add renovation, balcony, living_area, kitchen_area and deal_conditions columns to properties';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD renovation VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE properties ADD balcony VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE properties ADD living_area DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE properties ADD kitchen_area DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE properties ADD deal_conditions JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP COLUMN renovation');
        $this->addSql('ALTER TABLE properties DROP COLUMN balcony');
        $this->addSql('ALTER TABLE properties DROP COLUMN living_area');
        $this->addSql('ALTER TABLE properties DROP COLUMN kitchen_area');
        $this->addSql('ALTER TABLE properties DROP COLUMN deal_conditions');
    }
}
