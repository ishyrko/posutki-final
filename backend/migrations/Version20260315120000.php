<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260315120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add daily rent details fields to properties';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties
            ADD max_daily_guests INT DEFAULT NULL,
            ADD daily_bed_count INT DEFAULT NULL,
            ADD check_in_time VARCHAR(5) DEFAULT NULL,
            ADD check_out_time VARCHAR(5) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties
            DROP max_daily_guests,
            DROP daily_bed_count,
            DROP check_in_time,
            DROP check_out_time');
    }
}
