<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260726120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Dedup table for unique content views (property/article, per visitor per day)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE content_view_dedup (
                entity_type VARCHAR(20) NOT NULL,
                entity_id VARCHAR(64) NOT NULL,
                visitor_key VARCHAR(80) NOT NULL,
                view_date DATE NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                PRIMARY KEY (entity_type, entity_id, visitor_key, view_date)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE content_view_dedup');
    }
}
