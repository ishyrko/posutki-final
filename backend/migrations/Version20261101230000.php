<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Published free listings stay on the free tier after a later paid VIP.
 */
final class Version20261101230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set holds_free_slot for currently published free listings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'UPDATE properties
            SET holds_free_slot = 1
            WHERE status = \'published\'
              AND (
                  placement_base_level = 0
                  OR placement_is_trial = 1
                  OR placement_level_expires_at IS NULL
                  OR placement_level_expires_at <= NOW()
              )'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE properties SET holds_free_slot = 0');
    }
}
