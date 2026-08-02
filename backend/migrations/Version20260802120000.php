<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260802120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add city_districts table and city_district_id on properties';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE city_districts (
            id INT AUTO_INCREMENT NOT NULL,
            city_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_CITY_DISTRICTS_CITY_ID (city_id),
            UNIQUE INDEX UNIQ_CITY_DISTRICTS_CITY_NAME (city_id, name),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE city_districts ADD CONSTRAINT FK_CITY_DISTRICTS_CITY_ID FOREIGN KEY (city_id) REFERENCES cities (id)');
        $this->addSql('ALTER TABLE properties ADD city_district_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_PROPERTIES_CITY_DISTRICT_ID ON properties (city_district_id)');
        $this->addSql('ALTER TABLE properties ADD CONSTRAINT FK_PROPERTIES_CITY_DISTRICT_ID FOREIGN KEY (city_district_id) REFERENCES city_districts (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP FOREIGN KEY FK_PROPERTIES_CITY_DISTRICT_ID');
        $this->addSql('DROP INDEX IDX_PROPERTIES_CITY_DISTRICT_ID ON properties');
        $this->addSql('ALTER TABLE properties DROP city_district_id');
        $this->addSql('ALTER TABLE city_districts DROP FOREIGN KEY FK_CITY_DISTRICTS_CITY_ID');
        $this->addSql('DROP TABLE city_districts');
    }
}
