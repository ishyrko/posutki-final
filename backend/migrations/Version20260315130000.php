<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260315130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow nullable user email and add SMS auth phone codes table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users CHANGE email email VARCHAR(180) DEFAULT NULL COMMENT \'(DC2Type:email)\'');
        $this->addSql('CREATE TABLE auth_phone_codes (
            id CHAR(36) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            code VARCHAR(6) NOT NULL,
            expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_AUTH_PHONE_CODES_PHONE (phone),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE auth_phone_codes');
        $this->addSql('ALTER TABLE users CHANGE email email VARCHAR(180) NOT NULL COMMENT \'(DC2Type:email)\'');
    }
}
