<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Grant a 2-week VIP 1 trial to all currently published listings from the migration run date.
 */
final class Version20260730120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Grant 2-week VIP 1 trial to all published listings from migration date';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE properties
            SET free_trial_ends_at = DATE_ADD(NOW(), INTERVAL 2 WEEK)
            WHERE status = 'published'");

        $this->addSql("UPDATE properties
            SET placement_base_level = 1,
                placement_level_expires_at = free_trial_ends_at,
                placement_effective_level = 1,
                placement_is_trial = 1,
                placement_shuffle_key = IF(placement_shuffle_key <= 0, RAND(), placement_shuffle_key)
            WHERE status = 'published'
              AND free_trial_ends_at > NOW()");

        $this->addSql("UPDATE properties
            SET placement_effective_level = LEAST(placement_base_level + 1, 5)
            WHERE status = 'published'
              AND placement_boost_expires_at IS NOT NULL
              AND placement_boost_expires_at > NOW()
              AND placement_base_level > 0");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Bulk VIP trial grant cannot be safely reverted without a manual snapshot.',
        );
    }
}
