<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payments table for bePaid placement checkout';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE payments (
            id INT AUTO_INCREMENT NOT NULL,
            purchase_id INT NOT NULL,
            provider VARCHAR(30) NOT NULL,
            tracking_id VARCHAR(64) NOT NULL,
            checkout_token VARCHAR(128) DEFAULT NULL,
            transaction_uid VARCHAR(64) DEFAULT NULL,
            amount_byn INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            raw_payload LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            paid_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            failed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            note LONGTEXT DEFAULT NULL,
            UNIQUE INDEX uniq_payments_tracking_id (tracking_id),
            INDEX idx_payments_purchase (purchase_id),
            INDEX idx_payments_checkout_token (checkout_token),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE payments');
    }
}
