<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Seed VIP scope settings + level tariffs:
 * - apartments: every city that already has apartment listings
 * - houses: every region
 *
 * Prices/capacities mirror the Minsk baseline. Existing rows are left untouched.
 */
final class Version20260717120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed VIP tariffs for cities with apartment listings and all regions for houses';
    }

    public function up(Schema $schema): void
    {
        // --- Apartments: cities with listings ---
        $this->addSql("INSERT INTO property_placement_scope_settings (property_type, city_id, region_id, max_level, is_active)
            SELECT 'apartment', c.id, NULL, 5, 1
            FROM cities c
            WHERE EXISTS (
                SELECT 1 FROM properties p
                WHERE p.city_id = c.id AND p.type = 'apartment'
            )
            AND NOT EXISTS (
                SELECT 1 FROM property_placement_scope_settings s
                WHERE s.property_type = 'apartment' AND s.city_id = c.id
            )");

        $this->addSql("INSERT INTO property_placement_level_prices
            (property_type, city_id, region_id, level, capacity, price_byn_per_month, sort_order, is_active)
            SELECT 'apartment', c.id, NULL, v.level, v.capacity, v.price, v.sort_order, 1
            FROM cities c
            CROSS JOIN (
                SELECT 1 AS level, NULL AS capacity, 49 AS price, 1 AS sort_order
                UNION ALL SELECT 2, NULL, 119, 2
                UNION ALL SELECT 3, 20, 159, 3
                UNION ALL SELECT 4, 12, 269, 4
                UNION ALL SELECT 5, 8, 439, 5
            ) v
            WHERE EXISTS (
                SELECT 1 FROM properties p
                WHERE p.city_id = c.id AND p.type = 'apartment'
            )
            AND NOT EXISTS (
                SELECT 1 FROM property_placement_level_prices lp
                WHERE lp.property_type = 'apartment'
                  AND lp.city_id = c.id
                  AND lp.level = v.level
            )");

        // --- Houses: all regions ---
        $this->addSql("INSERT INTO property_placement_scope_settings (property_type, city_id, region_id, max_level, is_active)
            SELECT 'house', NULL, r.id, 5, 1
            FROM regions r
            WHERE NOT EXISTS (
                SELECT 1 FROM property_placement_scope_settings s
                WHERE s.property_type = 'house' AND s.region_id = r.id
            )");

        $this->addSql("INSERT INTO property_placement_level_prices
            (property_type, city_id, region_id, level, capacity, price_byn_per_month, sort_order, is_active)
            SELECT 'house', NULL, r.id, v.level, v.capacity, v.price, v.sort_order, 1
            FROM regions r
            CROSS JOIN (
                SELECT 1 AS level, NULL AS capacity, 49 AS price, 1 AS sort_order
                UNION ALL SELECT 2, NULL, 119, 2
                UNION ALL SELECT 3, 20, 159, 3
                UNION ALL SELECT 4, 12, 269, 4
                UNION ALL SELECT 5, 8, 439, 5
            ) v
            WHERE NOT EXISTS (
                SELECT 1 FROM property_placement_level_prices lp
                WHERE lp.property_type = 'house'
                  AND lp.region_id = r.id
                  AND lp.level = v.level
            )");
    }

    public function down(Schema $schema): void
    {
        // Keep Minsk apartments (seeded earlier); remove everything else introduced here.
        $this->addSql("DELETE lp FROM property_placement_level_prices lp
            INNER JOIN cities c ON c.id = lp.city_id
            WHERE lp.property_type = 'apartment' AND c.slug <> 'minsk'");

        $this->addSql("DELETE s FROM property_placement_scope_settings s
            INNER JOIN cities c ON c.id = s.city_id
            WHERE s.property_type = 'apartment' AND c.slug <> 'minsk'");

        $this->addSql("DELETE FROM property_placement_level_prices WHERE property_type = 'house'");
        $this->addSql("DELETE FROM property_placement_scope_settings WHERE property_type = 'house'");
    }
}
