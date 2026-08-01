<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260801150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link conversations to booking inquiries for chat context';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conversations
            ADD booking_inquiry_id INT DEFAULT NULL COMMENT \'(DC2Type:id)\'');
        $this->addSql('CREATE INDEX IDX_CONVERSATIONS_BOOKING_INQUIRY ON conversations (booking_inquiry_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_CONVERSATIONS_BOOKING_INQUIRY ON conversations');
        $this->addSql('ALTER TABLE conversations DROP booking_inquiry_id');
    }
}
