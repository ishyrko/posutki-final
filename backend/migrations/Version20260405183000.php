<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Shorten metro station 33 name (Pl. instead of Ploshchad)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE metro_stations SET name = 'Пл. Франтишка Богушевича' WHERE id = 33");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE metro_stations SET name = 'Площадь Франтишка Богушевича' WHERE id = 33");
    }
}
