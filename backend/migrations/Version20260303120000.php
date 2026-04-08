<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create property_revisions table for published listing edits moderation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE property_revisions (
            id CHAR(36) NOT NULL,
            property_id CHAR(36) NOT NULL,
            data JSON NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT \'pending\',
            moderation_comment LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            reviewed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            INDEX idx_property_revisions_property_status (property_id, status)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE property_revisions ADD CONSTRAINT FK_PROPERTY_REVISIONS_PROPERTY FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE property_revisions');
    }
}
