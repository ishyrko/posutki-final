<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reviews: consent, owner reply, seen flag, soft-delete status; drop unique property+author';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reviews DROP INDEX uniq_review_property_author');
        $this->addSql('ALTER TABLE reviews ADD share_data_with_owner TINYINT(1) DEFAULT 1 NOT NULL, ADD owner_reply LONGTEXT DEFAULT NULL, ADD owner_replied_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD owner_seen_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reviews DROP share_data_with_owner, DROP owner_reply, DROP owner_replied_at, DROP owner_seen_at');
        $this->addSql('CREATE UNIQUE INDEX uniq_review_property_author ON reviews (property_id, author_id)');
    }
}
