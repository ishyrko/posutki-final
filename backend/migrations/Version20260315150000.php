<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260315150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Switch ID columns from UUID to AUTO_INCREMENT integers.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->dropForeignKeyIfExists('property_revisions', 'FK_PROPERTY_REVISIONS_PROPERTY');
        $this->dropForeignKeyIfExists('property_metro_stations', 'FK_PROPERTY_METRO_PROPERTY');

        $this->addSql('TRUNCATE TABLE messages');
        $this->addSql('TRUNCATE TABLE conversations');
        $this->addSql('TRUNCATE TABLE favorites');
        $this->addSql('TRUNCATE TABLE user_phones');
        $this->addSql('TRUNCATE TABLE auth_phone_codes');
        $this->addSql('TRUNCATE TABLE contact_feedbacks');
        $this->addSql('TRUNCATE TABLE property_revisions');
        $this->addSql('TRUNCATE TABLE property_daily_stats');
        $this->addSql('TRUNCATE TABLE property_metro_stations');
        $this->addSql('TRUNCATE TABLE properties');
        $this->addSql('TRUNCATE TABLE articles');
        $this->addSql('TRUNCATE TABLE users');

        $this->addSql('ALTER TABLE users MODIFY id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE properties MODIFY id INT AUTO_INCREMENT NOT NULL, MODIFY owner_id INT NOT NULL');
        $this->addSql('ALTER TABLE articles MODIFY id INT AUTO_INCREMENT NOT NULL, MODIFY author_id INT NOT NULL');
        $this->addSql('ALTER TABLE favorites MODIFY id INT AUTO_INCREMENT NOT NULL, MODIFY user_id INT NOT NULL, MODIFY property_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_phones MODIFY id INT AUTO_INCREMENT NOT NULL, MODIFY user_id INT NOT NULL');
        $this->addSql('ALTER TABLE auth_phone_codes MODIFY id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE contact_feedbacks MODIFY id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE property_revisions MODIFY id INT AUTO_INCREMENT NOT NULL, MODIFY property_id INT NOT NULL');
        $this->addSql('ALTER TABLE conversations MODIFY id INT AUTO_INCREMENT NOT NULL, MODIFY property_id INT NOT NULL, MODIFY seller_id INT NOT NULL, MODIFY buyer_id INT NOT NULL');
        $this->addSql('ALTER TABLE messages MODIFY id INT AUTO_INCREMENT NOT NULL, MODIFY conversation_id INT NOT NULL, MODIFY sender_id INT NOT NULL');
        $this->addSql('ALTER TABLE property_daily_stats MODIFY id INT AUTO_INCREMENT NOT NULL, MODIFY property_id INT NOT NULL');
        $this->addSql('ALTER TABLE property_metro_stations MODIFY property_id INT NOT NULL');
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
        $this->throwIrreversibleMigrationException('This migration intentionally drops UUID-based identifiers and data.');
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

