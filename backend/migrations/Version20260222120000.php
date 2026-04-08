<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create conversations and messages tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE conversations (
                id VARCHAR(36) NOT NULL,
                property_id VARCHAR(36) NOT NULL,
                seller_id VARCHAR(36) NOT NULL,
                buyer_id VARCHAR(36) NOT NULL,
                last_message_text TEXT DEFAULT NULL,
                last_message_at DATETIME DEFAULT NULL,
                unread_seller INT NOT NULL DEFAULT 0,
                unread_buyer INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id)
            )
        ');

        $this->addSql('CREATE INDEX idx_conversations_property ON conversations (property_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_conversations_property_buyer ON conversations (property_id, buyer_id)');
        $this->addSql('CREATE INDEX idx_conversations_seller ON conversations (seller_id)');
        $this->addSql('CREATE INDEX idx_conversations_buyer ON conversations (buyer_id)');
        $this->addSql('CREATE INDEX idx_conversations_last_message ON conversations (last_message_at)');

        $this->addSql('
            CREATE TABLE messages (
                id VARCHAR(36) NOT NULL,
                conversation_id VARCHAR(36) NOT NULL,
                sender_id VARCHAR(36) NOT NULL,
                text TEXT NOT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id)
            )
        ');

        $this->addSql('CREATE INDEX idx_messages_conversation ON messages (conversation_id, created_at)');
        $this->addSql('CREATE INDEX idx_messages_sender ON messages (sender_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS messages');
        $this->addSql('DROP TABLE IF EXISTS conversations');
    }
}
