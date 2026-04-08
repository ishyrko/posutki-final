<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add moderation comment field and rejected status support for properties';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD moderation_comment LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP COLUMN moderation_comment');
    }
}
