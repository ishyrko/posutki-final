<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Посёлок Дружный (Пуховичский район) — отдельные объявления, не спутник Минска.
 */
final class Version20260917150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enable listing suggestions and apartment catalog for Druzhny (Pukhovichi district)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "UPDATE cities SET is_listing_suggested = 1, is_apartment_catalog = 1 WHERE slug = 'druzhnyy-p'",
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "UPDATE cities SET is_listing_suggested = 0, is_apartment_catalog = 0 WHERE slug = 'druzhnyy-p'",
        );
    }
}
