<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819120100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unused apartment_catalog_sort_order from cities';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('cities')->hasColumn('apartment_catalog_sort_order')) {
            return;
        }

        $this->addSql('ALTER TABLE cities DROP apartment_catalog_sort_order');
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('cities')->hasColumn('apartment_catalog_sort_order')) {
            return;
        }

        $this->addSql('ALTER TABLE cities ADD apartment_catalog_sort_order INT DEFAULT NULL');
    }
}
