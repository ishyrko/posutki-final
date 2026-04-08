<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove property type newbuilding: migrate existing rows to apartment';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE properties SET type = 'apartment' WHERE type = 'newbuilding'");
        $this->addSql(
            "UPDATE property_revisions
             SET data = JSON_SET(data, '$.type', 'apartment')
             WHERE JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) = 'newbuilding'",
        );
    }

    public function down(Schema $schema): void
    {
        // Irreversible: cannot restore which apartments were "newbuilding".
    }
}
