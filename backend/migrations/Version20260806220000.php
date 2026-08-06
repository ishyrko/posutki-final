<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260806220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unused landmarks.radius_km column (fixed 2 km proximity in application code)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE landmarks DROP radius_km');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE landmarks ADD radius_km DOUBLE PRECISION DEFAULT 2 NOT NULL');
    }
}
