<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create favorites table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE favorites (
            id CHAR(36) NOT NULL,
            user_id CHAR(36) NOT NULL,
            property_id CHAR(36) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            UNIQUE INDEX uniq_user_property (user_id, property_id),
            INDEX idx_favorites_user_created (user_id, created_at)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE favorites');
    }
}
