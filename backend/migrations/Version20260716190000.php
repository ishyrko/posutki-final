<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add base_purchase_id to property_placement_purchases for upgrade/renewal anchor tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property_placement_purchases
            ADD base_purchase_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_placement_purchases_base_purchase
            ON property_placement_purchases (base_purchase_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_placement_purchases_base_purchase ON property_placement_purchases');
        $this->addSql('ALTER TABLE property_placement_purchases DROP base_purchase_id');
    }
}
