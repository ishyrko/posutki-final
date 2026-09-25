<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925181000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'City flag, region centers, property distances to nearest city and regional center';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cities ADD is_city TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('CREATE INDEX idx_cities_is_city ON cities (is_city)');

        $this->addSql('ALTER TABLE regions ADD center_city_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE regions ADD CONSTRAINT FK_REGIONS_CENTER_CITY FOREIGN KEY (center_city_id) REFERENCES cities (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_REGIONS_CENTER_CITY ON regions (center_city_id)');

        $this->addSql('ALTER TABLE properties ADD nearest_city_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE properties ADD nearest_city_distance_km DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE properties ADD region_center_city_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE properties ADD region_center_distance_km DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE properties ADD CONSTRAINT FK_PROPERTIES_NEAREST_CITY FOREIGN KEY (nearest_city_id) REFERENCES cities (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE properties ADD CONSTRAINT FK_PROPERTIES_REGION_CENTER_CITY FOREIGN KEY (region_center_city_id) REFERENCES cities (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_properties_nearest_city ON properties (nearest_city_id, nearest_city_distance_km)');
        $this->addSql('CREATE INDEX idx_properties_region_center ON properties (region_center_city_id, region_center_distance_km)');

        $this->addSql("UPDATE cities SET is_city = 1 WHERE name LIKE '% г.' OR name NOT LIKE '% %'");

        /** @var array<string, array{lat: float, lon: float, genitive: string}> $seed */
        $seed = require __DIR__ . '/data/city_seed_data.php';
        foreach ($seed as $slug => $row) {
            $this->addSql(
                'UPDATE cities SET latitude = ?, longitude = ?, name_genitive = CASE WHEN name_genitive IS NULL OR name_genitive = \'\' THEN ? ELSE name_genitive END WHERE slug = ? AND is_city = 1',
                [(string) $row['lat'], (string) $row['lon'], $row['genitive'], $slug],
            );
        }

        foreach (['brest', 'vitebsk', 'gomel', 'grodno', 'minsk', 'mogilev'] as $regionSlug) {
            $this->addSql(
                'UPDATE regions SET center_city_id = (SELECT id FROM cities WHERE slug = ? AND is_main = 1 LIMIT 1) WHERE slug = ?',
                [$regionSlug, $regionSlug],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP FOREIGN KEY FK_PROPERTIES_NEAREST_CITY');
        $this->addSql('ALTER TABLE properties DROP FOREIGN KEY FK_PROPERTIES_REGION_CENTER_CITY');
        $this->addSql('DROP INDEX idx_properties_nearest_city ON properties');
        $this->addSql('DROP INDEX idx_properties_region_center ON properties');
        $this->addSql('ALTER TABLE properties DROP nearest_city_id, DROP nearest_city_distance_km, DROP region_center_city_id, DROP region_center_distance_km');

        $this->addSql('ALTER TABLE regions DROP FOREIGN KEY FK_REGIONS_CENTER_CITY');
        $this->addSql('DROP INDEX IDX_REGIONS_CENTER_CITY ON regions');
        $this->addSql('ALTER TABLE regions DROP center_city_id');

        $this->addSql('DROP INDEX idx_cities_is_city ON cities');
        $this->addSql('ALTER TABLE cities DROP is_city');
    }
}
