<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815130300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enable catalog SEO text and FAQ for all city room-count landing pages (buckets 1–3)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'UPDATE city_room_catalog_contents SET catalog_seo_visible = 1 WHERE rooms_bucket IN (1, 2, 3)',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'UPDATE city_room_catalog_contents SET catalog_seo_visible = 0 WHERE rooms_bucket IN (1, 2, 3)',
        );
    }
}
