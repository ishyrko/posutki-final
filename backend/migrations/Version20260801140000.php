<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260801140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store booking inquiry email reply history in owner_replies JSON';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking_inquiries ADD owner_replies JSON DEFAULT NULL');

        $this->addSql(<<<'SQL'
UPDATE booking_inquiries
SET owner_replies = JSON_ARRAY(
    JSON_OBJECT(
        'text', owner_reply,
        'repliedAt', CASE
            WHEN replied_at IS NULL THEN NULL
            ELSE DATE_FORMAT(replied_at, '%Y-%m-%dT%H:%i:%s+00:00')
        END
    )
)
WHERE owner_reply IS NOT NULL
SQL);

        $this->addSql("UPDATE booking_inquiries SET owner_replies = JSON_ARRAY() WHERE owner_replies IS NULL");
        $this->addSql('ALTER TABLE booking_inquiries MODIFY owner_replies JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking_inquiries DROP owner_replies');
    }
}
