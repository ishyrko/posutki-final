<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Partner account flags: is_partner, partner_listing_limit, is_trusted_publisher';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD is_partner TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE users ADD partner_listing_limit INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD is_trusted_publisher TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP is_trusted_publisher');
        $this->addSql('ALTER TABLE users DROP partner_listing_limit');
        $this->addSql('ALTER TABLE users DROP is_partner');
    }
}
