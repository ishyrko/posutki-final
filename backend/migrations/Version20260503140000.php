<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove listings that are not daily apartment/house (site scope: посуточно квартиры и дома)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
DELETE m FROM messages m
INNER JOIN conversations c ON m.conversation_id = c.id
INNER JOIN properties p ON c.property_id = p.id
WHERE p.type NOT IN ('apartment', 'house') OR p.deal_type != 'daily'
SQL);
        $this->addSql(<<<'SQL'
DELETE c FROM conversations c
INNER JOIN properties p ON c.property_id = p.id
WHERE p.type NOT IN ('apartment', 'house') OR p.deal_type != 'daily'
SQL);
        $this->addSql(<<<'SQL'
DELETE f FROM favorites f
INNER JOIN properties p ON f.property_id = p.id
WHERE p.type NOT IN ('apartment', 'house') OR p.deal_type != 'daily'
SQL);
        $this->addSql(<<<'SQL'
DELETE s FROM property_daily_stats s
INNER JOIN properties p ON s.property_id = p.id
WHERE p.type NOT IN ('apartment', 'house') OR p.deal_type != 'daily'
SQL);
        $this->addSql("DELETE FROM properties WHERE type NOT IN ('apartment', 'house') OR deal_type != 'daily'");
    }

    public function down(Schema $schema): void
    {
    }
}
