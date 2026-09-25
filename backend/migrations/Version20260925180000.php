<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add prepayment, extra check-in conditions and banquet seats to properties';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD prepayment_required TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE properties ADD additional_check_in_conditions LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE properties ADD banquet_seats INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP COLUMN prepayment_required');
        $this->addSql('ALTER TABLE properties DROP COLUMN additional_check_in_conditions');
        $this->addSql('ALTER TABLE properties DROP COLUMN banquet_seats');
    }
}
