<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Partner import mapped «Гомель»/«Речица»/«Старые Дороги» to agro-towns because
 * unsuffixed city names ranked worse than «аг.». Remap existing listings.
 */
final class Version20260917140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remap partner listings from agro-towns to the real Gomel, Rechitsa and Starye Dorogi cities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE cities SET is_listing_suggested = 1, is_apartment_catalog = 1 WHERE slug = 'rechitsa'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE cities SET is_apartment_catalog = 0 WHERE slug = 'rechitsa'");
    }
}
