<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add landmarks and property_landmarks tables for points of interest catalog filtering';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE landmarks (
            id INT AUTO_INCREMENT NOT NULL,
            city_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            latitude DOUBLE PRECISION NOT NULL,
            longitude DOUBLE PRECISION NOT NULL,
            radius_km DOUBLE PRECISION DEFAULT 1.5 NOT NULL,
            category VARCHAR(32) DEFAULT NULL,
            short_description LONGTEXT DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            image_url VARCHAR(512) DEFAULT NULL,
            catalog_location_phrase VARCHAR(255) DEFAULT NULL,
            meta_title VARCHAR(255) DEFAULT NULL,
            meta_description VARCHAR(512) DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1 NOT NULL,
            sort_order SMALLINT DEFAULT 0 NOT NULL,
            UNIQUE INDEX uniq_landmark_city_slug (city_id, slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE property_landmarks (
            property_id INT NOT NULL,
            landmark_id INT NOT NULL,
            distance_km DOUBLE PRECISION NOT NULL,
            INDEX idx_property_landmark_property (property_id),
            INDEX idx_property_landmark_landmark (landmark_id),
            PRIMARY KEY(property_id, landmark_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE property_landmarks');
        $this->addSql('DROP TABLE landmarks');
    }
}
