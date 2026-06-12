<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add video_url column to properties (YouTube/TikTok link)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE properties ADD COLUMN video_url VARCHAR(500) DEFAULT NULL AFTER website_url");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP COLUMN video_url');
    }
}
