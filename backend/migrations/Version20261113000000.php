<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261113000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add owner consent to publish a house listing on agroturizm.by';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD allow_agroturizm_publication TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP COLUMN allow_agroturizm_publication');
    }
}
