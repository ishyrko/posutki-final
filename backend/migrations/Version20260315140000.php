<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260315140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename address apartment key to block in JSON data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "UPDATE properties
             SET address = JSON_SET(address, '$.block', JSON_EXTRACT(address, '$.apartment'))
             WHERE JSON_EXTRACT(address, '$.block') IS NULL
               AND JSON_EXTRACT(address, '$.apartment') IS NOT NULL"
        );
        $this->addSql(
            "UPDATE properties
             SET address = JSON_REMOVE(address, '$.apartment')
             WHERE JSON_EXTRACT(address, '$.apartment') IS NOT NULL"
        );

        $this->addSql(
            "UPDATE property_revisions
             SET data = JSON_SET(data, '$.block', JSON_EXTRACT(data, '$.apartment'))
             WHERE JSON_EXTRACT(data, '$.block') IS NULL
               AND JSON_EXTRACT(data, '$.apartment') IS NOT NULL"
        );
        $this->addSql(
            "UPDATE property_revisions
             SET data = JSON_REMOVE(data, '$.apartment')
             WHERE JSON_EXTRACT(data, '$.apartment') IS NOT NULL"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "UPDATE properties
             SET address = JSON_SET(address, '$.apartment', JSON_EXTRACT(address, '$.block'))
             WHERE JSON_EXTRACT(address, '$.apartment') IS NULL
               AND JSON_EXTRACT(address, '$.block') IS NOT NULL"
        );
        $this->addSql(
            "UPDATE properties
             SET address = JSON_REMOVE(address, '$.block')
             WHERE JSON_EXTRACT(address, '$.block') IS NOT NULL"
        );

        $this->addSql(
            "UPDATE property_revisions
             SET data = JSON_SET(data, '$.apartment', JSON_EXTRACT(data, '$.block'))
             WHERE JSON_EXTRACT(data, '$.apartment') IS NULL
               AND JSON_EXTRACT(data, '$.block') IS NOT NULL"
        );
        $this->addSql(
            "UPDATE property_revisions
             SET data = JSON_REMOVE(data, '$.block')
             WHERE JSON_EXTRACT(data, '$.block') IS NOT NULL"
        );
    }
}
