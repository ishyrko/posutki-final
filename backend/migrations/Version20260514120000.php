<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260514120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add additional_services, instagram_url, website_url columns to properties (for houses)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE properties ADD COLUMN additional_services JSON DEFAULT NULL AFTER weekend_price_negotiable");
        $this->addSql("ALTER TABLE properties ADD COLUMN instagram_url VARCHAR(500) DEFAULT NULL AFTER additional_services");
        $this->addSql("ALTER TABLE properties ADD COLUMN website_url VARCHAR(500) DEFAULT NULL AFTER instagram_url");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE properties DROP COLUMN additional_services");
        $this->addSql("ALTER TABLE properties DROP COLUMN instagram_url");
        $this->addSql("ALTER TABLE properties DROP COLUMN website_url");
    }
}
