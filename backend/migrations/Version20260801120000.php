<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260801120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Booking inquiry replies, status, calendar block link; user guest inquiry setting';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking_inquiries
            ADD status VARCHAR(20) NOT NULL DEFAULT \'new\',
            ADD owner_reply LONGTEXT DEFAULT NULL,
            ADD replied_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            ADD availability_block_id INT DEFAULT NULL COMMENT \'(DC2Type:id)\'');

        $this->addSql('ALTER TABLE users
            ADD allow_guest_booking_inquiries TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP allow_guest_booking_inquiries');

        $this->addSql('ALTER TABLE booking_inquiries
            DROP status,
            DROP owner_reply,
            DROP replied_at,
            DROP availability_block_id');
    }
}
