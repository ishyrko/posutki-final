<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track VIP expiry reminder email (placement_level_expiry_reminded_at)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE properties ADD placement_level_expiry_reminded_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\''
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP placement_level_expiry_reminded_at');
    }
}
