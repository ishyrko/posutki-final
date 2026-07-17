<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unused price_per_meter_byn (sale/per-meter catalog filter removed)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_properties_price_per_meter_byn ON properties');
        $this->addSql('ALTER TABLE properties DROP COLUMN price_per_meter_byn');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD price_per_meter_byn INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_properties_price_per_meter_byn ON properties (price_per_meter_byn)');
        $this->addSql('UPDATE properties SET price_per_meter_byn = ROUND(price_byn / area) WHERE price_byn IS NOT NULL AND area > 0');
    }
}
