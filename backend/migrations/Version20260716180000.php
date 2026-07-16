<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate placement from slots to VIP tiers: new level prices, scope settings, property/purchase schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE property_placement_level_prices (
            id INT AUTO_INCREMENT NOT NULL,
            property_type VARCHAR(20) NOT NULL,
            city_id INT DEFAULT NULL,
            region_id INT DEFAULT NULL,
            level INT NOT NULL,
            capacity INT DEFAULT NULL,
            price_byn_per_month INT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            INDEX idx_placement_levels_city_active (city_id, is_active),
            INDEX idx_placement_levels_region_active (region_id, is_active),
            UNIQUE INDEX uniq_placement_level_apartment_city (property_type, city_id, level),
            UNIQUE INDEX uniq_placement_level_house_region (property_type, region_id, level),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE property_placement_scope_settings (
            id INT AUTO_INCREMENT NOT NULL,
            property_type VARCHAR(20) NOT NULL,
            city_id INT DEFAULT NULL,
            region_id INT DEFAULT NULL,
            max_level INT NOT NULL DEFAULT 5,
            boost_price_byn INT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            UNIQUE INDEX uniq_placement_scope_apartment_city (property_type, city_id),
            UNIQUE INDEX uniq_placement_scope_house_region (property_type, region_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql("ALTER TABLE properties
            ADD placement_base_level INT NOT NULL DEFAULT 0,
            ADD placement_level_expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD placement_boost_expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD placement_effective_level INT NOT NULL DEFAULT 0");

        $this->addSql("UPDATE properties SET
            placement_base_level = CASE placement_type
                WHEN 'special' THEN 5
                WHEN 'standard' THEN 1
                ELSE 0
            END,
            placement_level_expires_at = placement_expires_at,
            placement_effective_level = CASE placement_type
                WHEN 'special' THEN 5
                WHEN 'standard' THEN 1
                ELSE 0
            END");

        $this->addSql("UPDATE properties SET
            placement_base_level = 1,
            placement_level_expires_at = free_trial_ends_at,
            placement_effective_level = 1
            WHERE placement_is_trial = 1
              AND free_trial_ends_at IS NOT NULL
              AND free_trial_ends_at > NOW()");

        $this->addSql('ALTER TABLE properties
            DROP placement_type,
            DROP placement_slot_rank,
            DROP placement_expires_at,
            DROP boosted_at');

        $this->addSql("ALTER TABLE property_placement_purchases
            ADD kind VARCHAR(20) NOT NULL DEFAULT 'level',
            ADD level INT DEFAULT NULL,
            ADD level_price_id INT DEFAULT NULL");

        $this->addSql("UPDATE property_placement_purchases SET
            kind = 'level',
            level = CASE type
                WHEN 'special' THEN 5
                WHEN 'standard' THEN 1
                ELSE NULL
            END");

        $this->addSql('ALTER TABLE property_placement_purchases
            DROP type,
            DROP slot_id,
            MODIFY duration_months INT DEFAULT NULL');

        $this->addSql('DROP INDEX idx_placement_purchases_slot_status ON property_placement_purchases');
        $this->addSql('CREATE INDEX idx_placement_purchases_level_price_status
            ON property_placement_purchases (level_price_id, status)');

        $this->addSql('DROP TABLE property_placement_slots');
        $this->addSql('DROP TABLE property_placement_standard_prices');

        // Minsk apartments: VIP tiers and scope settings
        $this->addSql("INSERT INTO property_placement_scope_settings (property_type, city_id, region_id, max_level, boost_price_byn, is_active)
            SELECT 'apartment', c.id, NULL, 5, 15, 1
            FROM cities c WHERE c.slug = 'minsk'");

        $this->addSql("INSERT INTO property_placement_level_prices (property_type, city_id, region_id, level, capacity, price_byn_per_month, sort_order, is_active)
            SELECT 'apartment', c.id, NULL, v.level, v.capacity, v.price, v.sort_order, 1
            FROM cities c
            CROSS JOIN (
                SELECT 1 AS level, NULL AS capacity, 49 AS price, 1 AS sort_order
                UNION ALL SELECT 2, NULL, 119, 2
                UNION ALL SELECT 3, 20, 159, 3
                UNION ALL SELECT 4, 12, 269, 4
                UNION ALL SELECT 5, 8, 439, 5
            ) v
            WHERE c.slug = 'minsk'");

        $this->addSql("UPDATE property_placement_purchases p
            INNER JOIN properties pr ON pr.id = p.property_id
            INNER JOIN property_placement_level_prices lp
                ON lp.property_type = 'apartment'
                AND lp.city_id = pr.city_id
                AND lp.level = p.level
            SET p.level_price_id = lp.id
            WHERE p.kind = 'level' AND p.level IS NOT NULL AND p.level_price_id IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE property_placement_slots (
            id INT AUTO_INCREMENT NOT NULL,
            property_type VARCHAR(20) NOT NULL,
            city_id INT DEFAULT NULL,
            region_id INT DEFAULT NULL,
            rank_from INT NOT NULL,
            rank_to INT NOT NULL,
            capacity INT NOT NULL,
            price_byn_per_month INT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            INDEX idx_placement_slots_city_active (city_id, is_active),
            INDEX idx_placement_slots_region_active (region_id, is_active),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE property_placement_standard_prices (
            id INT AUTO_INCREMENT NOT NULL,
            property_type VARCHAR(20) NOT NULL,
            city_id INT DEFAULT NULL,
            region_id INT DEFAULT NULL,
            price_byn_per_month INT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            UNIQUE INDEX uniq_placement_standard_apartment_city (property_type, city_id),
            UNIQUE INDEX uniq_placement_standard_house_region (property_type, region_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql("ALTER TABLE properties
            ADD placement_type VARCHAR(20) NOT NULL DEFAULT 'free',
            ADD placement_slot_rank INT DEFAULT NULL,
            ADD placement_expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD boosted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");

        $this->addSql("UPDATE properties SET
            placement_type = CASE placement_base_level
                WHEN 5 THEN 'special'
                WHEN 1 THEN 'standard'
                ELSE 'free'
            END,
            placement_expires_at = placement_level_expires_at");

        $this->addSql('ALTER TABLE properties
            DROP placement_base_level,
            DROP placement_level_expires_at,
            DROP placement_boost_expires_at,
            DROP placement_effective_level');

        $this->addSql("ALTER TABLE property_placement_purchases
            ADD type VARCHAR(20) NOT NULL DEFAULT 'standard',
            ADD slot_id INT DEFAULT NULL");

        $this->addSql("UPDATE property_placement_purchases SET
            type = CASE level
                WHEN 5 THEN 'special'
                WHEN 1 THEN 'standard'
                ELSE 'standard'
            END
            WHERE kind = 'level'");

        $this->addSql('ALTER TABLE property_placement_purchases
            DROP kind,
            DROP level,
            DROP level_price_id,
            MODIFY duration_months INT NOT NULL');

        $this->addSql('DROP INDEX idx_placement_purchases_level_price_status ON property_placement_purchases');
        $this->addSql('CREATE INDEX idx_placement_purchases_slot_status ON property_placement_purchases (slot_id, status)');

        $this->addSql('DROP TABLE property_placement_level_prices');
        $this->addSql('DROP TABLE property_placement_scope_settings');
    }
}
