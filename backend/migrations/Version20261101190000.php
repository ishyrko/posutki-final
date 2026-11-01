<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * A free listing keeps its quota place when the owner buys VIP for it.
 * Listings published only because of payment do not take a free slot.
 */
final class Version20261101190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remember which published listings hold a free slot through paid VIP';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD holds_free_slot TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql(
            'UPDATE properties p
            LEFT JOIN (
                SELECT property_id, MIN(activated_at) AS first_paid_at
                FROM property_placement_purchases
                WHERE kind = \'level\' AND activated_at IS NOT NULL
                GROUP BY property_id
            ) first_paid ON first_paid.property_id = p.id
            SET p.holds_free_slot = 1
            WHERE p.status = \'published\'
              AND (
                NOT (
                    p.placement_base_level > 0
                    AND p.placement_is_trial = 0
                    AND p.placement_level_expires_at IS NOT NULL
                    AND p.placement_level_expires_at > NOW()
                )
                OR first_paid.first_paid_at IS NULL
                OR (
                    p.published_at IS NOT NULL
                    AND p.published_at < DATE_SUB(first_paid.first_paid_at, INTERVAL 2 MINUTE)
                )
              )'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP holds_free_slot');
    }
}
