<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260514200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create reviews table (property reviews with moderation)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reviews (
            id INT AUTO_INCREMENT NOT NULL,
            property_id INT NOT NULL,
            author_id INT NOT NULL,
            rating SMALLINT NOT NULL,
            text LONGTEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            moderation_comment VARCHAR(500) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            UNIQUE INDEX uniq_review_property_author (property_id, author_id),
            INDEX idx_reviews_property_status (property_id, status),
            CONSTRAINT FK_reviews_property FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE,
            CONSTRAINT FK_reviews_author FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE reviews');
    }
}
