<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop legacy boost_price_byn from property_placement_scope_settings (boost price is derived from level tariffs)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property_placement_scope_settings DROP boost_price_byn');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property_placement_scope_settings
            ADD boost_price_byn INT NOT NULL DEFAULT 0');
    }
}
