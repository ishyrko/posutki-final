<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add boosted_at for free daily property boost (sorting in catalog)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE properties ADD boosted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP boosted_at');
    }
}
