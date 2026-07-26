<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260726140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow anonymous favorites via visitor_id for statistics';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE favorites MODIFY user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE favorites ADD visitor_id VARCHAR(64) DEFAULT NULL AFTER user_id');
        $this->addSql('CREATE UNIQUE INDEX uniq_visitor_property ON favorites (visitor_id, property_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM favorites WHERE user_id IS NULL');
        $this->addSql('DROP INDEX uniq_visitor_property ON favorites');
        $this->addSql('ALTER TABLE favorites DROP visitor_id');
        $this->addSql('ALTER TABLE favorites MODIFY user_id INT NOT NULL');
    }
}
