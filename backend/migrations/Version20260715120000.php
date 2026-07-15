<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add property placement fields, slots, standard prices, purchases; seed Minsk; backfill free trial';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE properties
            ADD placement_type VARCHAR(20) NOT NULL DEFAULT 'free',
            ADD placement_slot_rank INT DEFAULT NULL,
            ADD placement_expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD placement_is_trial TINYINT(1) NOT NULL DEFAULT 0,
            ADD free_trial_ends_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD placement_shuffle_key DOUBLE PRECISION NOT NULL DEFAULT 0");

        $this->addSql('CREATE TABLE property_placement_slots (
            id INT AUTO_INCREMENT NOT NULL,
            city_id INT NOT NULL,
            rank_from INT NOT NULL,
            rank_to INT NOT NULL,
            capacity INT NOT NULL,
            price_byn_per_month INT NOT NULL,
            is_top_slot TINYINT(1) NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            INDEX idx_placement_slots_city_active (city_id, is_active),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE property_placement_standard_prices (
            id INT AUTO_INCREMENT NOT NULL,
            city_id INT NOT NULL,
            price_byn_per_month INT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            UNIQUE INDEX uniq_placement_standard_city (city_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE property_placement_purchases (
            id INT AUTO_INCREMENT NOT NULL,
            property_id INT NOT NULL,
            owner_id INT NOT NULL,
            type VARCHAR(20) NOT NULL,
            slot_id INT DEFAULT NULL,
            duration_months INT NOT NULL,
            price_byn INT NOT NULL,
            status VARCHAR(30) NOT NULL,
            source VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            activated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            reservation_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            activated_by_admin_id INT DEFAULT NULL,
            note LONGTEXT DEFAULT NULL,
            INDEX idx_placement_purchases_property_status (property_id, status),
            INDEX idx_placement_purchases_slot_status (slot_id, status),
            INDEX idx_placement_purchases_owner (owner_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Seed Minsk slots (city slug = minsk) using approximate kvartirka.by pricing
        $this->addSql("INSERT INTO property_placement_slots (city_id, rank_from, rank_to, capacity, price_byn_per_month, is_top_slot, sort_order, is_active)
            SELECT c.id, v.rank_from, v.rank_to, v.capacity, v.price, v.is_top, v.sort_order, 1
            FROM cities c
            CROSS JOIN (
                SELECT 1 AS rank_from, 1 AS rank_to, 2 AS capacity, 439 AS price, 1 AS is_top, 1 AS sort_order
                UNION ALL SELECT 2, 6, 5, 379, 0, 2
                UNION ALL SELECT 7, 12, 6, 329, 0, 3
                UNION ALL SELECT 13, 18, 6, 269, 0, 4
                UNION ALL SELECT 19, 24, 6, 219, 0, 5
                UNION ALL SELECT 25, 30, 6, 159, 0, 6
                UNION ALL SELECT 31, 36, 6, 129, 0, 7
                UNION ALL SELECT 37, 42, 6, 119, 0, 8
            ) v
            WHERE c.slug = 'minsk'");

        $this->addSql("INSERT INTO property_placement_standard_prices (city_id, price_byn_per_month, is_active)
            SELECT c.id, 49, 1 FROM cities c WHERE c.slug = 'minsk'");

        // Backfill: trial window from published_at; active trial → standard, else free
        $this->addSql("UPDATE properties
            SET free_trial_ends_at = DATE_ADD(published_at, INTERVAL 1 MONTH),
                placement_shuffle_key = RAND()
            WHERE status = 'published' AND published_at IS NOT NULL AND free_trial_ends_at IS NULL");

        $this->addSql("UPDATE properties
            SET placement_type = 'standard',
                placement_is_trial = 1,
                placement_expires_at = free_trial_ends_at,
                placement_slot_rank = NULL
            WHERE status = 'published'
              AND free_trial_ends_at IS NOT NULL
              AND free_trial_ends_at > NOW()");

        $this->addSql("UPDATE properties
            SET placement_type = 'free',
                placement_is_trial = 0,
                placement_expires_at = NULL,
                placement_slot_rank = NULL
            WHERE status = 'published'
              AND (free_trial_ends_at IS NULL OR free_trial_ends_at <= NOW())
              AND placement_type = 'free'");

        // Ensure published with expired trial are free
        $this->addSql("UPDATE properties
            SET placement_type = 'free',
                placement_is_trial = 0,
                placement_expires_at = NULL,
                placement_slot_rank = NULL
            WHERE status = 'published'
              AND free_trial_ends_at IS NOT NULL
              AND free_trial_ends_at <= NOW()");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE property_placement_purchases');
        $this->addSql('DROP TABLE property_placement_standard_prices');
        $this->addSql('DROP TABLE property_placement_slots');
        $this->addSql('ALTER TABLE properties
            DROP placement_type,
            DROP placement_slot_rank,
            DROP placement_expires_at,
            DROP placement_is_trial,
            DROP free_trial_ends_at,
            DROP placement_shuffle_key');
    }
}
