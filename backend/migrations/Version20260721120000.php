<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add min_stay_days for daily apartment and house listings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD min_stay_days INT DEFAULT NULL');
        $this->addSql("UPDATE properties SET min_stay_days = 1 WHERE deal_type = 'daily'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP min_stay_days');
    }
}
