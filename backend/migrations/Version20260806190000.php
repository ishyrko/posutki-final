<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260806190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add landmark address, facts, guest tips; allow nullable coordinates for geocoding';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE landmarks
            ADD address VARCHAR(512) DEFAULT NULL,
            ADD facts JSON DEFAULT NULL,
            ADD guest_tips JSON DEFAULT NULL,
            MODIFY latitude DOUBLE PRECISION DEFAULT NULL,
            MODIFY longitude DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE landmarks
            DROP address,
            DROP facts,
            DROP guest_tips,
            MODIFY latitude DOUBLE PRECISION NOT NULL,
            MODIFY longitude DOUBLE PRECISION NOT NULL');
    }
}
