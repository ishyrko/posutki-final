<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop is_top_slot from property_placement_slots';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property_placement_slots DROP is_top_slot');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property_placement_slots ADD is_top_slot TINYINT(1) NOT NULL DEFAULT 0');
    }
}
