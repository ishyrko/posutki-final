<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sync placement slot capacity from rank_from/rank_to';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE property_placement_slots SET capacity = rank_to - rank_from + 1');
    }

    public function down(Schema $schema): void
    {
        // Irreversible: previous capacity values were manually overridden in seed data.
    }
}
