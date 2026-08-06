<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260806210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unused landmark SEO and catalog location phrase columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE landmarks
            DROP catalog_location_phrase,
            DROP meta_title,
            DROP meta_description');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE landmarks
            ADD catalog_location_phrase VARCHAR(255) DEFAULT NULL,
            ADD meta_title VARCHAR(255) DEFAULT NULL,
            ADD meta_description VARCHAR(512) DEFAULT NULL');
    }
}
