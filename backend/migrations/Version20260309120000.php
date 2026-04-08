<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create contact_feedbacks table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE contact_feedbacks (
            id CHAR(36) NOT NULL,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(180) NOT NULL COMMENT \'(DC2Type:email)\',
            subject VARCHAR(180) NOT NULL,
            message LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            INDEX idx_contact_feedbacks_created_at (created_at)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE contact_feedbacks');
    }
}
