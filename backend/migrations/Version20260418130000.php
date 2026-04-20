<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sms_send_log for SMS rate limiting and abuse prevention';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sms_send_log (
            id INT AUTO_INCREMENT NOT NULL,
            phone VARCHAR(20) NOT NULL,
            ip VARCHAR(45) NOT NULL,
            user_id INT DEFAULT NULL,
            channel VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_SMS_SEND_LOG_PHONE_CREATED (phone, created_at),
            INDEX IDX_SMS_SEND_LOG_IP_CREATED (ip, created_at),
            INDEX IDX_SMS_SEND_LOG_CREATED (created_at),
            PRIMARY KEY(id),
            CONSTRAINT FK_SMS_SEND_LOG_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sms_send_log');
    }
}
