<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add catalog_faq column to cities for per-city catalog FAQ blocks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cities ADD catalog_faq JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cities DROP catalog_faq');
    }
}
