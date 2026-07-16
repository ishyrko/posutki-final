<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add users.has_used_free_placement_trial; backfill from properties with free_trial_ends_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD has_used_free_placement_trial TINYINT(1) NOT NULL DEFAULT 0');

        $this->addSql('UPDATE users u
            INNER JOIN (
                SELECT DISTINCT owner_id
                FROM properties
                WHERE free_trial_ends_at IS NOT NULL
            ) t ON t.owner_id = u.id
            SET u.has_used_free_placement_trial = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP has_used_free_placement_trial');
    }
}
