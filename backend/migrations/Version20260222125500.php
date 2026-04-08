<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222125500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_conversations_buyer ON conversations');
        $this->addSql('DROP INDEX idx_conversations_seller ON conversations');
        $this->addSql('DROP INDEX idx_conversations_last_message ON conversations');
        $this->addSql('ALTER TABLE conversations CHANGE id id CHAR(36) NOT NULL, CHANGE last_message_text last_message_text LONGTEXT DEFAULT NULL, CHANGE unread_seller unread_seller INT NOT NULL, CHANGE unread_buyer unread_buyer INT NOT NULL');
        $this->addSql('ALTER TABLE conversations RENAME INDEX idx_conversations_property TO IDX_C2521BF1549213EC');
        $this->addSql('ALTER TABLE conversations RENAME INDEX uniq_conversations_property_buyer TO UNIQ_C2521BF1549213EC6C755722');
        $this->addSql('DROP INDEX idx_messages_sender ON messages');
        $this->addSql('ALTER TABLE messages CHANGE id id CHAR(36) NOT NULL, CHANGE text text LONGTEXT NOT NULL, CHANGE is_read is_read TINYINT NOT NULL');
        $this->addSql('ALTER TABLE messages RENAME INDEX idx_messages_conversation TO IDX_DB021E969AC03968B8E8428');
        $this->addSql('ALTER TABLE users ADD reset_password_token VARCHAR(64) DEFAULT NULL, ADD reset_password_token_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conversations CHANGE id id VARCHAR(36) NOT NULL, CHANGE last_message_text last_message_text TEXT DEFAULT NULL, CHANGE unread_seller unread_seller INT DEFAULT 0 NOT NULL, CHANGE unread_buyer unread_buyer INT DEFAULT 0 NOT NULL');
        $this->addSql('CREATE INDEX idx_conversations_buyer ON conversations (buyer_id)');
        $this->addSql('CREATE INDEX idx_conversations_seller ON conversations (seller_id)');
        $this->addSql('CREATE INDEX idx_conversations_last_message ON conversations (last_message_at)');
        $this->addSql('ALTER TABLE conversations RENAME INDEX uniq_c2521bf1549213ec6c755722 TO uniq_conversations_property_buyer');
        $this->addSql('ALTER TABLE conversations RENAME INDEX idx_c2521bf1549213ec TO idx_conversations_property');
        $this->addSql('ALTER TABLE messages CHANGE id id VARCHAR(36) NOT NULL, CHANGE text text TEXT NOT NULL, CHANGE is_read is_read TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('CREATE INDEX idx_messages_sender ON messages (sender_id)');
        $this->addSql('ALTER TABLE messages RENAME INDEX idx_db021e969ac03968b8e8428 TO idx_messages_conversation');
        $this->addSql('ALTER TABLE users DROP reset_password_token, DROP reset_password_token_expires_at');
    }
}
