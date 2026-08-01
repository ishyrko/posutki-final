<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260801130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User setting to allow or disable messages and booking inquiries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users
            ADD allow_messages_and_inquiries TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP allow_messages_and_inquiries');
    }
}
