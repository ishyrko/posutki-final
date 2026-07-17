<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * placement_shuffle_key: DOUBLE → INT; catalog composite index for filter + VIP/shuffle ORDER BY.
 */
final class Version20260717140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert placement_shuffle_key to INT and add catalog placement composite index';
    }

    public function up(Schema $schema): void
    {
        // Scale [0,1) floats from RAND()/mt_rand ratio into the signed INT range before narrowing type.
        $this->addSql('UPDATE properties
            SET placement_shuffle_key = CASE
                WHEN placement_shuffle_key <= 0 THEN 0
                WHEN placement_shuffle_key < 1 THEN FLOOR(placement_shuffle_key * 2147483647)
                ELSE FLOOR(placement_shuffle_key)
            END');

        $this->addSql('ALTER TABLE properties
            CHANGE placement_shuffle_key placement_shuffle_key INT NOT NULL DEFAULT 0');

        $this->addSql('DROP INDEX IDX_87C331C78BAC62AF3AA332868CDE57297B00651C ON properties');

        // ASC index: catalog ORDER BY uses both columns DESC (reverse scan on MySQL 5.7).
        $this->addSql('CREATE INDEX IDX_PROPERTIES_CATALOG_PLACEMENT ON properties (
            city_id,
            deal_type,
            type,
            status,
            placement_effective_level,
            placement_shuffle_key
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_PROPERTIES_CATALOG_PLACEMENT ON properties');

        $this->addSql('CREATE INDEX IDX_87C331C78BAC62AF3AA332868CDE57297B00651C ON properties (city_id, deal_type, type, status)');

        $this->addSql('ALTER TABLE properties
            CHANGE placement_shuffle_key placement_shuffle_key DOUBLE PRECISION NOT NULL DEFAULT 0');

        // Approximate reverse: map INT back into (0,1) for legacy float semantics.
        $this->addSql('UPDATE properties
            SET placement_shuffle_key = CASE
                WHEN placement_shuffle_key <= 0 THEN 0
                ELSE placement_shuffle_key / 2147483647
            END');
    }
}
