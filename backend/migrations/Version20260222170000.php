<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_main column to cities table and mark 6 main cities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cities ADD is_main TINYINT(1) NOT NULL DEFAULT 0');

        $this->addSql("UPDATE cities SET is_main = 1 WHERE slug IN ('minsk', 'brest', 'vitebsk', 'gomel', 'grodno', 'mogilev')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cities DROP COLUMN is_main');
    }
}
