<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Corrective pass: force trial VIP 1 for 2 months on all published listings,
 * overriding any previously active paid placement left by the first migration run.
 */
final class Version20260719190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Override active paid VIP with 2-month trial VIP 1 on all published listings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE properties
            SET free_trial_ends_at = DATE_ADD(NOW(), INTERVAL 2 MONTH)
            WHERE status = 'published'");

        $this->addSql("UPDATE properties
            SET placement_base_level = 1,
                placement_level_expires_at = free_trial_ends_at,
                placement_effective_level = 1,
                placement_is_trial = 1,
                placement_level_expiry_reminded_at = NOW()
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
            'Trial VIP override cannot be safely reverted without a manual snapshot.',
        );
    }
}
