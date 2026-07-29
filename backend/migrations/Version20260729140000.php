<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Smaller apartment cities (not Minsk / oblast centers): max VIP 4,
 * VIP 3 capacity 8, VIP 4 capacity 4; VIP 5 deactivated.
 */
final class Version20260729140000 extends AbstractMigration
{
    private const TIER_3_MAX_LEVEL = 4;

    /** @var list<string> */
    private const OBLAST_CENTER_CITY_SLUGS = [
        'brest',
        'vitebsk',
        'gomel',
        'grodno',
        'mogilev',
    ];

    public function getDescription(): string
    {
        return 'Cap smaller apartment cities at VIP 4 with tier-3/4 slot limits';
    }

    public function up(Schema $schema): void
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

    public function down(Schema $schema): void
    {
        $excluded = $this->quoteSlugList([
            'minsk',
            ...self::OBLAST_CENTER_CITY_SLUGS,
        ]);

        $this->addSql(sprintf(
            "UPDATE property_placement_scope_settings s
            INNER JOIN cities c ON c.id = s.city_id
            SET s.max_level = 5
            WHERE s.property_type = 'apartment'
              AND c.slug NOT IN (%s)",
            $excluded,
        ));

        $this->addSql(sprintf(
            "UPDATE property_placement_level_prices lp
            INNER JOIN cities c ON c.id = lp.city_id
            SET lp.capacity = CASE lp.level
                WHEN 3 THEN 20
                WHEN 4 THEN 12
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
            SET lp.is_active = 1
            WHERE lp.property_type = 'apartment'
              AND c.slug NOT IN (%s)
              AND lp.level = 5",
            $excluded,
        ));
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
