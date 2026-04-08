<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_phones table, contact fields to properties, is_phone_verified to users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_phones (
            id CHAR(36) NOT NULL,
            user_id CHAR(36) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            is_verified TINYINT(1) NOT NULL DEFAULT 0,
            code VARCHAR(6) DEFAULT NULL,
            code_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_USER_PHONES_USER (user_id),
            UNIQUE INDEX UQ_USER_PHONE (user_id, phone),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE properties ADD contact_phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE properties ADD contact_name VARCHAR(100) DEFAULT NULL');

        $this->addSql('ALTER TABLE users ADD is_phone_verified TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_phones');
        $this->addSql('ALTER TABLE properties DROP contact_phone');
        $this->addSql('ALTER TABLE properties DROP contact_name');
        $this->addSql('ALTER TABLE users DROP is_phone_verified');
    }
}
