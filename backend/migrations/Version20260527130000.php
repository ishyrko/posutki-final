<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_read column to booking_inquiries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking_inquiries ADD is_read TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('CREATE INDEX idx_booking_inquiries_owner_read ON booking_inquiries (owner_id, is_read)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_booking_inquiries_owner_read ON booking_inquiries');
        $this->addSql('ALTER TABLE booking_inquiries DROP is_read');
    }
}
