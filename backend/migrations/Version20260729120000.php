<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * VIP placement tariffs by geography (apartments by city slug, houses by region slug):
 * - Minsk / Minsk region: 35, 99, 150, 250, 350
 * - Oblast centers + their regions: 25, 50, 100, 150, 200
 * - Cities with URL city prefix (CITY_PREFIX_SLUGS): 15, 25, 35, 45, 60
 *
 * Creates missing scope settings and level rows, then sets prices (upsert).
 */
final class Version20260729120000 extends AbstractMigration
{
    /** @var list<int|null> */
    private const LEVEL_CAPACITIES = [null, null, 20, 12, 8];

    /** @var list<int> */
    private const TIER_1_PRICES = [35, 99, 150, 250, 350];

    /** @var list<int> */
    private const TIER_2_PRICES = [25, 50, 100, 150, 200];

    /** @var list<int> */
    private const TIER_3_PRICES = [15, 25, 35, 45];

    private const TIER_3_MAX_LEVEL = 4;

    /** @var list<int|null> VIP 1–4 capacities for smaller cities (not Minsk / oblast centers). */
    private const TIER_3_CAPACITIES = [null, null, 8, 4];

    /** @var list<int> */
    private const LEGACY_PRICES = [49, 119, 159, 269, 439];

    /** @var list<string> */
    private const OBLAST_CENTER_CITY_SLUGS = [
        'brest',
        'vitebsk',
        'gomel',
        'grodno',
        'mogilev',
    ];

    /** @var list<string> */
    private const CITY_PREFIX_SLUGS = [
        'orsha',
        'svetlogorsk',
        'smorgon',
        'molodechno',
        'logoysk',
        'baranovichi',
        'pinsk',
        'novopolotsk',
        'bobruysk',
        'zhlobin',
        'volkovysk',
    ];

    public function getDescription(): string
    {
        return 'Upsert VIP placement level prices by city/region tier';
    }

    public function up(Schema $schema): void
    {
        $this->upsertApartmentTier(['minsk'], self::TIER_1_PRICES, 5, self::LEVEL_CAPACITIES);
        $this->upsertApartmentTier(self::OBLAST_CENTER_CITY_SLUGS, self::TIER_2_PRICES, 5, self::LEVEL_CAPACITIES);
        $this->upsertApartmentTier(self::CITY_PREFIX_SLUGS, self::TIER_3_PRICES, self::TIER_3_MAX_LEVEL, self::TIER_3_CAPACITIES);
        $this->deactivateApartmentLevelFive(self::CITY_PREFIX_SLUGS);
        $this->applySmallerCityApartmentScopeRules();

        $this->upsertHouseTier(['minsk'], self::TIER_1_PRICES);
        $this->upsertHouseTier(self::OBLAST_CENTER_CITY_SLUGS, self::TIER_2_PRICES);
    }

    public function down(Schema $schema): void
    {
        $allCitySlugs = array_values(array_unique([
            'minsk',
            ...self::OBLAST_CENTER_CITY_SLUGS,
            ...self::CITY_PREFIX_SLUGS,
        ]));
        $allRegionSlugs = array_values(array_unique([
            'minsk',
            ...self::OBLAST_CENTER_CITY_SLUGS,
        ]));

        $this->updateApartmentPrices($allCitySlugs, self::LEGACY_PRICES);
        $this->updateHousePrices($allRegionSlugs, self::LEGACY_PRICES);
    }

    /**
     * @param list<string>       $citySlugs
     * @param list<int>          $pricesByLevel index 0 = VIP 1 …
     * @param list<int|null>|null $capacitiesByLevel parallel to pricesByLevel; defaults to LEVEL_CAPACITIES prefix
     */
    private function upsertApartmentTier(
        array $citySlugs,
        array $pricesByLevel,
        int $maxLevel = 5,
        ?array $capacitiesByLevel = null,
    ): void {
        if ($citySlugs === []) {
            return;
        }

        $slugList = $this->quoteSlugList($citySlugs);
        $capacitiesByLevel ??= array_slice(self::LEVEL_CAPACITIES, 0, count($pricesByLevel));

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

    private function applySmallerCityApartmentScopeRules(): void
    {
        $excluded = $this->quoteSlugList([
            'minsk',
            ...self::OBLAST_CENTER_CITY_SLUGS,
        ]);

        $this->addSql(sprintf(
            "UPDATE property_placement_scope_settings s
            INNER JOIN cities c ON c.id = s.city_id
            SET s.max_level = %d
            WHERE s.property_type = 'apartment'
              AND c.slug NOT IN (%s)",
            self::TIER_3_MAX_LEVEL,
            $excluded,
        ));

        $this->addSql(sprintf(
            "UPDATE property_placement_level_prices lp
            INNER JOIN cities c ON c.id = lp.city_id
            SET lp.capacity = CASE lp.level
                WHEN 3 THEN 8
                WHEN 4 THEN 4
                ELSE lp.capacity
            END
            WHERE lp.property_type = 'apartment'
              AND c.slug NOT IN (%s)
              AND lp.level IN (3, 4)",
            $excluded,
        ));

        $this->addSql(sprintf(
            "UPDATE property_placement_level_prices lp
            INNER JOIN cities c ON c.id = lp.city_id
            SET lp.is_active = 0
            WHERE lp.property_type = 'apartment'
              AND c.slug NOT IN (%s)
              AND lp.level = 5",
            $excluded,
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
     * @param list<string> $regionSlugs
     * @param list<int>    $pricesByLevel index 0 = VIP 1 … index 4 = VIP 5
     */
    private function upsertHouseTier(array $regionSlugs, array $pricesByLevel): void
    {
        if ($regionSlugs === []) {
            return;
        }

        $slugList = $this->quoteSlugList($regionSlugs);

        $this->addSql(sprintf(
            "INSERT INTO property_placement_scope_settings (property_type, city_id, region_id, max_level, is_active)
            SELECT 'house', NULL, r.id, 5, 1
            FROM regions r
            WHERE r.slug IN (%s)
            ON DUPLICATE KEY UPDATE max_level = VALUES(max_level), is_active = VALUES(is_active)",
            $slugList,
        ));

        $this->addSql(sprintf(
            "INSERT INTO property_placement_level_prices
                (property_type, city_id, region_id, level, capacity, price_byn_per_month, sort_order, is_active)
            SELECT 'house', NULL, r.id, v.level, v.capacity, v.price, v.sort_order, 1
            FROM regions r
            CROSS JOIN (%s) v
            WHERE r.slug IN (%s)
            ON DUPLICATE KEY UPDATE price_byn_per_month = VALUES(price_byn_per_month)",
            $this->buildLevelValuesUnion($pricesByLevel, self::LEVEL_CAPACITIES),
            $slugList,
        ));
    }

    /**
     * @param list<string> $citySlugs
     * @param list<int>    $pricesByLevel index 0 = VIP 1 … index 4 = VIP 5
     */
    private function updateApartmentPrices(array $citySlugs, array $pricesByLevel): void
    {
        if ($citySlugs === []) {
            return;
        }

        $this->addSql(sprintf(
            "UPDATE property_placement_level_prices lp
            INNER JOIN cities c ON c.id = lp.city_id
            SET lp.price_byn_per_month = %s
            WHERE lp.property_type = 'apartment'
              AND c.slug IN (%s)",
            $this->buildLevelPriceCaseSql($pricesByLevel),
            $this->quoteSlugList($citySlugs),
        ));
    }

    /**
     * @param list<string> $regionSlugs
     * @param list<int>    $pricesByLevel index 0 = VIP 1 … index 4 = VIP 5
     */
    private function updateHousePrices(array $regionSlugs, array $pricesByLevel): void
    {
        if ($regionSlugs === []) {
            return;
        }

        $this->addSql(sprintf(
            "UPDATE property_placement_level_prices lp
            INNER JOIN regions r ON r.id = lp.region_id
            SET lp.price_byn_per_month = %s
            WHERE lp.property_type = 'house'
              AND r.slug IN (%s)",
            $this->buildLevelPriceCaseSql($pricesByLevel),
            $this->quoteSlugList($regionSlugs),
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
     * @param list<int> $pricesByLevel index 0 = VIP 1 … index 4 = VIP 5
     */
    private function buildLevelPriceCaseSql(array $pricesByLevel): string
    {
        $parts = [];
        foreach ($pricesByLevel as $index => $price) {
            $level = $index + 1;
            $parts[] = "WHEN {$level} THEN {$price}";
        }

        return 'CASE lp.level ' . implode(' ', $parts) . ' ELSE lp.price_byn_per_month END';
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
