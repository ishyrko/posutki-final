<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Split placement slots by property type: city for apartments, region for houses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property_placement_slots
            ADD property_type VARCHAR(20) NOT NULL DEFAULT \'apartment\' AFTER id,
            ADD region_id INT DEFAULT NULL AFTER city_id');

        $this->addSql('UPDATE property_placement_slots SET property_type = \'apartment\'');

        $this->addSql('ALTER TABLE property_placement_slots
            MODIFY city_id INT DEFAULT NULL');

        $this->addSql('CREATE INDEX idx_placement_slots_region_active
            ON property_placement_slots (region_id, is_active)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_placement_slots_region_active ON property_placement_slots');

        $this->addSql('DELETE FROM property_placement_slots WHERE property_type = \'house\'');

        $this->addSql('ALTER TABLE property_placement_slots
            DROP property_type,
            DROP region_id,
            MODIFY city_id INT NOT NULL');
    }
}
