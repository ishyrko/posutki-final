<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260806200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add required landmark name_genitive for catalog meta titles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE landmarks ADD name_genitive VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addSql('UPDATE landmarks SET name_genitive = name WHERE name_genitive = \'\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE landmarks DROP name_genitive');
    }
}
