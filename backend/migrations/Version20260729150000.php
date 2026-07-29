<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Extend active VIP on all listings that currently have a non-expired placement level
 * to one month from the migration run date.
 */
final class Version20260729150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set placement_level_expires_at to +1 month for listings with active VIP';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE properties
            SET placement_level_expires_at = DATE_ADD(NOW(), INTERVAL 1 MONTH),
                free_trial_ends_at = CASE
                    WHEN placement_is_trial = 1 THEN DATE_ADD(NOW(), INTERVAL 1 MONTH)
                    ELSE free_trial_ends_at
                END,
                placement_level_expiry_reminded_at = NULL
            WHERE placement_base_level > 0
              AND placement_level_expires_at IS NOT NULL
              AND placement_level_expires_at > NOW()");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Previous VIP expiry dates cannot be restored without a manual snapshot.',
        );
    }
}
