<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221144443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cities RENAME INDEX idx_2d5b023467a397a8 TO IDX_D95DB16B67A397A8');
        $this->addSql('ALTER TABLE properties ADD street_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE region_districts CHANGE code code VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE region_districts RENAME INDEX idx_4206507598260155 TO IDX_3B234CE898260155');
        $this->addSql('ALTER TABLE regions CHANGE code code VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE streets RENAME INDEX idx_f0eed3d88bac62af TO IDX_93F67B3E8BAC62AF');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cities RENAME INDEX idx_d95db16b67a397a8 TO IDX_2D5B023467A397A8');
        $this->addSql('ALTER TABLE properties DROP street_id');
        $this->addSql('ALTER TABLE regions CHANGE code code VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE region_districts CHANGE code code VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE region_districts RENAME INDEX idx_3b234ce898260155 TO IDX_4206507598260155');
        $this->addSql('ALTER TABLE streets RENAME INDEX idx_93f67b3e8bac62af TO IDX_F0EED3D88BAC62AF');
    }
}
