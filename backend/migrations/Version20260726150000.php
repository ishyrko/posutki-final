<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260726150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add append-only favorite_add_events table for non-decreasing favorite statistics';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE favorite_add_events (
            id INT AUTO_INCREMENT NOT NULL,
            property_id INT NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            INDEX idx_favorite_add_events_property_created (property_id, created_at)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('INSERT INTO favorite_add_events (property_id, created_at)
            SELECT property_id, created_at FROM favorites');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE favorite_add_events');
    }
}
