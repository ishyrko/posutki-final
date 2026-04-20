<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Unique verified profile phone: generated column verified_phone + unique index';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD verified_phone VARCHAR(20) GENERATED ALWAYS AS (CASE WHEN is_phone_verified = 1 AND phone IS NOT NULL THEN phone END) STORED');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_users_verified_phone ON users (verified_phone)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_users_verified_phone ON users');
        $this->addSql('ALTER TABLE users DROP COLUMN verified_phone');
    }
}
