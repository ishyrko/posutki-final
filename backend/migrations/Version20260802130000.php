<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260802130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add property_geocoder_results table for caching Yandex Geocoder API responses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE property_geocoder_results (
            id INT AUTO_INCREMENT NOT NULL,
            property_id INT NOT NULL,
            latitude DOUBLE PRECISION NOT NULL,
            longitude DOUBLE PRECISION NOT NULL,
            response JSON NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_PROPERTY_GEOCODER_PROPERTY (property_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE property_geocoder_results ADD CONSTRAINT FK_PROPERTY_GEOCODER_PROPERTY FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property_geocoder_results DROP FOREIGN KEY FK_PROPERTY_GEOCODER_PROPERTY');
        $this->addSql('DROP TABLE property_geocoder_results');
    }
}
