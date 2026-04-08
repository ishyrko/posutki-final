<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add rooms_in_deal and rooms_area for room sale/rent listings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties ADD rooms_in_deal INT DEFAULT NULL, ADD rooms_area DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP rooms_in_deal, DROP rooms_area');
    }
}
