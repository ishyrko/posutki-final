<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Grant a one-time 2-month VIP 1 trial to all currently published listings,
 * mark every user as having consumed the free placement trial, and suppress
 * VIP expiry reminder emails for the granted period.
 */
final class Version20260719180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Grant 2-month VIP 1 trial to published listings; mark all users trial-used; suppress expiry emails';
    }

    public function up(Schema $schema): void
    {
        // Trial window: 2 months from migration run for every published listing.
        $this->addSql("UPDATE properties
            SET free_trial_ends_at = DATE_ADD(NOW(), INTERVAL 2 MONTH)
            WHERE status = 'published'");

        // Force VIP 1 trial for every published listing (overrides any active paid VIP).
        $this->addSql("UPDATE properties
            SET placement_base_level = 1,
                placement_level_expires_at = free_trial_ends_at,
                placement_effective_level = 1,
                placement_is_trial = 1,
                placement_level_expiry_reminded_at = NOW(),
                placement_shuffle_key = IF(placement_shuffle_key <= 0, RAND(), placement_shuffle_key)
            WHERE status = 'published'
              AND free_trial_ends_at > NOW()");

        // Preserve active VIP-boost on top of the newly granted trial level.
        $this->addSql("UPDATE properties
            SET placement_effective_level = LEAST(placement_base_level + 1, 5)
            WHERE status = 'published'
              AND placement_boost_expires_at IS NOT NULL
              AND placement_boost_expires_at > NOW()
              AND placement_base_level > 0");

        // One-time trial consumed for every account (no automatic trial on future publications).
        $this->addSql('UPDATE users SET has_used_free_placement_trial = 1');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Bulk VIP trial grant cannot be safely reverted without a manual snapshot.',
        );
    }
}
