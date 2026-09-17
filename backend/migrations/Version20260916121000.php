<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'External source id on properties for idempotent partner import';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD external_source VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE properties ADD external_id VARCHAR(100) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_properties_external_source_id ON properties (external_source, external_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_properties_external_source_id ON properties');
        $this->addSql('ALTER TABLE properties DROP external_id');
        $this->addSql('ALTER TABLE properties DROP external_source');
    }
}
