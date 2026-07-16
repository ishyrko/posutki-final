<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Split standard placement prices by property type: city for apartments, region for houses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property_placement_standard_prices
            ADD property_type VARCHAR(20) NOT NULL DEFAULT \'apartment\' AFTER id,
            ADD region_id INT DEFAULT NULL AFTER city_id,
            DROP INDEX uniq_placement_standard_city');

        $this->addSql('UPDATE property_placement_standard_prices SET property_type = \'apartment\' WHERE property_type = \'apartment\'');

        $this->addSql('ALTER TABLE property_placement_standard_prices
            MODIFY city_id INT DEFAULT NULL');

        $this->addSql('CREATE UNIQUE INDEX uniq_placement_standard_apartment_city
            ON property_placement_standard_prices (property_type, city_id)');

        $this->addSql('CREATE UNIQUE INDEX uniq_placement_standard_house_region
            ON property_placement_standard_prices (property_type, region_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_placement_standard_apartment_city ON property_placement_standard_prices');
        $this->addSql('DROP INDEX uniq_placement_standard_house_region ON property_placement_standard_prices');

        $this->addSql('DELETE FROM property_placement_standard_prices WHERE property_type = \'house\'');

        $this->addSql('ALTER TABLE property_placement_standard_prices
            DROP property_type,
            DROP region_id,
            MODIFY city_id INT NOT NULL');

        $this->addSql('CREATE UNIQUE INDEX uniq_placement_standard_city ON property_placement_standard_prices (city_id)');
    }
}
