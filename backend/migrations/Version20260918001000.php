<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918001000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Indexes for owner cabinet/stats and homepage fresh listings without using the nullable unique external_source key';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IDX_PROPERTIES_OWNER_STATUS ON properties (owner_id, status)');
        $this->addSql('CREATE INDEX IDX_PROPERTIES_STATUS_EXTERNAL_PUBLISHED ON properties (status, external_source, published_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_PROPERTIES_OWNER_STATUS ON properties');
        $this->addSql('DROP INDEX IDX_PROPERTIES_STATUS_EXTERNAL_PUBLISHED ON properties');
    }
}
