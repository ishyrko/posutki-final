<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260317120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make rooms/floor/total_floors nullable for type-specific property fields';
    }

    public function up(Schema $schema): void
    {
        // MySQL/MariaDB: MODIFY … NULL (PostgreSQL-style ALTER COLUMN … DROP NOT NULL is not supported)
        $this->addSql('ALTER TABLE properties MODIFY rooms INT DEFAULT NULL');
        $this->addSql('ALTER TABLE properties MODIFY floor INT DEFAULT NULL');
        $this->addSql('ALTER TABLE properties MODIFY total_floors INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE properties SET rooms = 1 WHERE rooms IS NULL');
        $this->addSql('UPDATE properties SET floor = 1 WHERE floor IS NULL');
        $this->addSql('UPDATE properties SET total_floors = 1 WHERE total_floors IS NULL');
        $this->addSql('ALTER TABLE properties MODIFY rooms INT NOT NULL');
        $this->addSql('ALTER TABLE properties MODIFY floor INT NOT NULL');
        $this->addSql('ALTER TABLE properties MODIFY total_floors INT NOT NULL');
    }
}
