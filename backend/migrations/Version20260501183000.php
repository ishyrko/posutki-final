<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260501183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Split daily bed count into single and double beds columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD daily_single_beds INT DEFAULT NULL, ADD daily_double_beds INT DEFAULT NULL');
        $this->addSql('UPDATE properties SET daily_single_beds = COALESCE(daily_bed_count, 0), daily_double_beds = 0');
        $this->addSql('ALTER TABLE properties DROP daily_bed_count');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD daily_bed_count INT DEFAULT NULL');
        $this->addSql('UPDATE properties SET daily_bed_count = COALESCE(daily_single_beds, 0) + COALESCE(daily_double_beds, 0)');
        $this->addSql('ALTER TABLE properties DROP daily_single_beds, DROP daily_double_beds');
    }
}
