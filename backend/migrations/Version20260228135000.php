<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228135000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create property daily stats table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE property_daily_stats (
            id CHAR(36) NOT NULL,
            property_id CHAR(36) NOT NULL,
            stat_date DATE NOT NULL,
            views INT DEFAULT 0 NOT NULL,
            phone_views INT DEFAULT 0 NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX uq_property_date (property_id, stat_date),
            INDEX idx_property_daily_stats_property_date (property_id, stat_date)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE property_daily_stats');
    }
}
