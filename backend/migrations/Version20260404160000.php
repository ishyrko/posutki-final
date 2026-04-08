<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove property type commercial: migrate existing rows to office';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE properties SET type = 'office' WHERE type = 'commercial'");
        $this->addSql(
            "UPDATE property_revisions
             SET data = JSON_SET(data, '$.type', 'office')
             WHERE JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) = 'commercial'",
        );
    }

    public function down(Schema $schema): void
    {
        // Irreversible: cannot restore which offices were generic "commercial".
    }
}
