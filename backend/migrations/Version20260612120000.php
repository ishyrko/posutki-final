<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260612120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add owner calendar: availability blocks, export token, external calendar snapshot';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE property_availability_blocks (
                id INT AUTO_INCREMENT NOT NULL,
                property_id INT NOT NULL COMMENT '(DC2Type:id)',
                start_date DATE NOT NULL COMMENT '(DC2Type:date_immutable)',
                end_date DATE NOT NULL COMMENT '(DC2Type:date_immutable)',
                note VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX idx_property_availability_blocks_property (property_id),
                INDEX idx_property_availability_blocks_dates (start_date, end_date),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);

        $this->addSql('ALTER TABLE properties ADD calendar_export_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PROPERTIES_CALENDAR_EXPORT_TOKEN ON properties (calendar_export_token)');
        $this->addSql('ALTER TABLE properties ADD external_calendar_snapshot JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE properties ADD external_calendar_synced_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE property_availability_blocks');
        $this->addSql('DROP INDEX UNIQ_PROPERTIES_CALENDAR_EXPORT_TOKEN ON properties');
        $this->addSql('ALTER TABLE properties DROP calendar_export_token');
        $this->addSql('ALTER TABLE properties DROP external_calendar_snapshot');
        $this->addSql('ALTER TABLE properties DROP external_calendar_synced_at');
    }
}
