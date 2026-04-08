<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228131000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add near_metro flag to properties and create property_metro_stations relation table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD near_metro TINYINT(1) NOT NULL DEFAULT 0');

        $this->addSql(
            'CREATE TABLE property_metro_stations (
                property_id CHAR(36) NOT NULL,
                metro_station_id INT NOT NULL,
                distance_km DOUBLE PRECISION NOT NULL,
                INDEX idx_property_metro_property (property_id),
                INDEX idx_property_metro_station (metro_station_id),
                PRIMARY KEY(property_id, metro_station_id),
                CONSTRAINT FK_PROPERTY_METRO_PROPERTY FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE,
                CONSTRAINT FK_PROPERTY_METRO_STATION FOREIGN KEY (metro_station_id) REFERENCES metro_stations (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE property_metro_stations');
        $this->addSql('ALTER TABLE properties DROP COLUMN near_metro');
    }
}
