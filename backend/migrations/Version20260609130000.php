<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Version20260609120000 created auth_refresh_tokens with UUID columns;
 * project IDs are INT AUTO_INCREMENT (see Version20260315150000).
 */
final class Version20260609130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix auth_refresh_tokens id/user_id column types (INT AUTO_INCREMENT)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS auth_refresh_tokens');
        $this->addSql('CREATE TABLE auth_refresh_tokens (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_AUTH_REFRESH_TOKEN_HASH (token_hash),
            INDEX IDX_AUTH_REFRESH_USER (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE auth_refresh_tokens');
        $this->addSql('CREATE TABLE auth_refresh_tokens (
            id CHAR(36) NOT NULL,
            user_id CHAR(36) NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_AUTH_REFRESH_TOKEN_HASH (token_hash),
            INDEX IDX_AUTH_REFRESH_USER (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }
}
