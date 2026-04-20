<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * One-time production prep: remove all users and property listings (and dependent rows).
 * Articles, categories, cities, metro, static pages, exchange rates, and contact feedbacks are left unchanged.
 * Article rows keep their author_id values (orphan ids; no FK to users in schema).
 */
final class Version20260418120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Truncate users, properties, and related tables for production launch; reset auto-increment; keep articles.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->dropForeignKeyIfExists('property_revisions', 'FK_PROPERTY_REVISIONS_PROPERTY');
        $this->dropForeignKeyIfExists('property_metro_stations', 'FK_PROPERTY_METRO_PROPERTY');

        // Order: children first. TRUNCATE clears data and resets AUTO_INCREMENT on tables that use it.
        $this->addSql('TRUNCATE TABLE messages');
        $this->addSql('TRUNCATE TABLE conversations');
        $this->addSql('TRUNCATE TABLE favorites');
        $this->addSql('TRUNCATE TABLE user_phones');
        $this->addSql('TRUNCATE TABLE auth_phone_codes');
        $this->addSql('TRUNCATE TABLE property_revisions');
        $this->addSql('TRUNCATE TABLE property_daily_stats');
        $this->addSql('TRUNCATE TABLE property_metro_stations');
        $this->addSql('TRUNCATE TABLE properties');
        $this->addSql('TRUNCATE TABLE users');

        $this->addForeignKeyIfMissing(
            'property_revisions',
            'FK_PROPERTY_REVISIONS_PROPERTY',
            'ALTER TABLE property_revisions ADD CONSTRAINT FK_PROPERTY_REVISIONS_PROPERTY FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE'
        );
        $this->addForeignKeyIfMissing(
            'property_metro_stations',
            'FK_PROPERTY_METRO_PROPERTY',
            'ALTER TABLE property_metro_stations ADD CONSTRAINT FK_PROPERTY_METRO_PROPERTY FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE'
        );

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Data wipe migration cannot be reversed.');
    }

    private function dropForeignKeyIfExists(string $table, string $constraint): void
    {
        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$table, $constraint]
        ) > 0;

        if ($exists) {
            $this->addSql(sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $table, $constraint));
        }
    }

    private function addForeignKeyIfMissing(string $table, string $constraint, string $sql): void
    {
        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$table, $constraint]
        ) > 0;

        if (!$exists) {
            $this->addSql($sql);
        }
    }
}
