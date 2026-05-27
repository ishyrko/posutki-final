<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create booking_inquiries table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE booking_inquiries (
            id INT AUTO_INCREMENT NOT NULL,
            property_id INT NOT NULL COMMENT \'(DC2Type:id)\',
            owner_id INT NOT NULL COMMENT \'(DC2Type:id)\',
            user_id INT DEFAULT NULL COMMENT \'(DC2Type:id)\',
            name VARCHAR(120) NOT NULL,
            phone VARCHAR(32) NOT NULL,
            email VARCHAR(180) DEFAULT NULL,
            guests INT DEFAULT NULL,
            check_in DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\',
            check_out DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\',
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_booking_inquiries_owner (owner_id),
            INDEX idx_booking_inquiries_property (property_id),
            INDEX idx_booking_inquiries_created_at (created_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE booking_inquiries');
    }
}
