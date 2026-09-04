<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cities.free_apartments_per_account and properties city/type/status index for free listing limits';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cities ADD free_apartments_per_account INT NOT NULL DEFAULT 1');
        $this->addSql('CREATE INDEX IDX_PROPERTIES_CITY_TYPE_STATUS ON properties (city_id, type, status)');
        $this->addSql(
            'UPDATE cities c SET free_apartments_per_account = GREATEST(1, 10 - (
                SELECT COUNT(*) FROM properties p
                WHERE p.city_id = c.id AND p.type = \'apartment\' AND p.status = \'published\'
            ))'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_PROPERTIES_CITY_TYPE_STATUS ON properties');
        $this->addSql('ALTER TABLE cities DROP free_apartments_per_account');
    }
}
