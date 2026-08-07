<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Version20260719180000 set has_used_free_placement_trial = 1 for every user,
 * including accounts that never owned a listing. Restore eligibility only for
 * those users so first-publish free VIP 1 still works for them.
 */
final class Version20260807120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reset has_used_free_placement_trial for users who never had any properties';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE users u
            LEFT JOIN properties p ON p.owner_id = u.id
            SET u.has_used_free_placement_trial = 0
            WHERE u.has_used_free_placement_trial = 1
              AND p.id IS NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
