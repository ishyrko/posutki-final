<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tier-3 VIP placement tariffs for catalog city-prefix slug lida.
 */
final class Version20260822120100 extends AbstractMigration
{
    /** @var list<int> */
    private const TIER_3_PRICES = [15, 25, 35, 45];

    private const TIER_3_MAX_LEVEL = 4;

    /** @var list<int|null> */
    private const TIER_3_CAPACITIES = [null, null, 8, 4];

    /** @var list<string> */
    private const CITY_PREFIX_SLUGS = [
        'lida',
    ];

    public function getDescription(): string
    {
        return 'Upsert tier-3 VIP placement tariffs for lida';
    }

    public function up(Schema $schema): void
    {
        $this->upsertApartmentTier(self::CITY_PREFIX_SLUGS, self::TIER_3_PRICES, self::TIER_3_MAX_LEVEL, self::TIER_3_CAPACITIES);
        $this->deactivateApartmentLevelFive(self::CITY_PREFIX_SLUGS);
    }

    public function down(Schema $schema): void
    {
        $slugList = $this->quoteSlugList(self::CITY_PREFIX_SLUGS);

        $this->addSql(sprintf(
            "DELETE lp FROM property_placement_level_prices lp
            INNER JOIN cities c ON c.id = lp.city_id
            WHERE lp.property_type = 'apartment'
              AND c.slug IN (%s)",
            $slugList,
        ));

        $this->addSql(sprintf(
            "DELETE s FROM property_placement_scope_settings s
            INNER JOIN cities c ON c.id = s.city_id
            WHERE s.property_type = 'apartment'
              AND c.slug IN (%s)",
            $slugList,
        ));
    }

    /**
     * @param list<string>       $citySlugs
     * @param list<int>          $pricesByLevel index 0 = VIP 1 …
     * @param list<int|null>     $capacitiesByLevel parallel to pricesByLevel
     */
    private function upsertApartmentTier(
        array $citySlugs,
        array $pricesByLevel,
        int $maxLevel = 4,
        ?array $capacitiesByLevel = null,
    ): void {
        if ($citySlugs === []) {
            return;
        }

        $slugList = $this->quoteSlugList($citySlugs);
        $capacitiesByLevel ??= self::TIER_3_CAPACITIES;

        $this->addSql(sprintf(
            "INSERT INTO property_placement_scope_settings (property_type, city_id, region_id, max_level, is_active)
            SELECT 'apartment', c.id, NULL, %d, 1
            FROM cities c
            WHERE c.slug IN (%s)
            ON DUPLICATE KEY UPDATE max_level = VALUES(max_level), is_active = VALUES(is_active)",
            $maxLevel,
            $slugList,
        ));

        $this->addSql(sprintf(
            "INSERT INTO property_placement_level_prices
                (property_type, city_id, region_id, level, capacity, price_byn_per_month, sort_order, is_active)
            SELECT 'apartment', c.id, NULL, v.level, v.capacity, v.price, v.sort_order, 1
            FROM cities c
            CROSS JOIN (%s) v
            WHERE c.slug IN (%s)
            ON DUPLICATE KEY UPDATE
                price_byn_per_month = VALUES(price_byn_per_month),
                capacity = VALUES(capacity),
                is_active = 1",
            $this->buildLevelValuesUnion($pricesByLevel, $capacitiesByLevel),
            $slugList,
        ));
    }

    /**
     * @param list<string> $citySlugs
     */
    private function deactivateApartmentLevelFive(array $citySlugs): void
    {
        if ($citySlugs === []) {
            return;
        }

        $this->addSql(sprintf(
            "UPDATE property_placement_level_prices lp
            INNER JOIN cities c ON c.id = lp.city_id
            SET lp.is_active = 0
            WHERE lp.property_type = 'apartment'
              AND c.slug IN (%s)
              AND lp.level = 5",
            $this->quoteSlugList($citySlugs),
        ));
    }

    /**
     * @param list<int>          $pricesByLevel index 0 = VIP 1 …
     * @param list<int|null>     $capacitiesByLevel parallel to pricesByLevel
     */
    private function buildLevelValuesUnion(array $pricesByLevel, array $capacitiesByLevel): string
    {
        $parts = [];
        foreach ($pricesByLevel as $index => $price) {
            $level = $index + 1;
            $capacity = $capacitiesByLevel[$index] ?? null;
            $capacitySql = $capacity === null ? 'NULL' : (string) $capacity;
            $parts[] = "SELECT {$level} AS level, {$capacitySql} AS capacity, {$price} AS price, {$level} AS sort_order";
        }

        return implode(' UNION ALL ', $parts);
    }

    /**
     * @param list<string> $slugs
     */
    private function quoteSlugList(array $slugs): string
    {
        return implode(', ', array_map(
            static fn (string $slug): string => "'" . str_replace("'", "''", $slug) . "'",
            $slugs,
        ));
    }
}
